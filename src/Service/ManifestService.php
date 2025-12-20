<?php
declare(strict_types=1);

namespace CakeVite\Service;

use CakeVite\Exception\ManifestException;
use CakeVite\ValueObject\ManifestCollection;
use CakeVite\ValueObject\ManifestEntry;
use CakeVite\ValueObject\ViteConfig;
use JsonException;

/**
 * Handles manifest file loading and parsing
 *
 * Stateless service for reading and parsing Vite manifest files.
 * Implements in-memory caching to avoid duplicate file reads within
 * the same request lifecycle.
 */
final class ManifestService
{
    /**
     * In-memory cache of loaded manifests (per request)
     *
     * @var array<string, \CakeVite\ValueObject\ManifestCollection>
     */
    private static array $manifestCache = [];

    /**
     * Load and parse manifest file with in-memory caching
     *
     * Caches parsed manifests by path to avoid duplicate file reads
     * within the same request lifecycle. Cache is automatically cleared
     * between HTTP requests.
     *
     * @param \CakeVite\ValueObject\ViteConfig $config Configuration
     * @return \CakeVite\ValueObject\ManifestCollection Collection of manifest entries
     * @throws \CakeVite\Exception\ManifestException
     */
    public function load(ViteConfig $config): ManifestCollection
    {
        $manifestPath = $this->resolveManifestPath($config);

        // Return cached manifest if already loaded
        if (isset(self::$manifestCache[$manifestPath])) {
            return self::$manifestCache[$manifestPath];
        }

        if (!is_readable($manifestPath)) {
            throw new ManifestException(
                sprintf('Manifest file not found or not readable at: %s. ', $manifestPath) .
                "Did you run 'npm run build' or 'vite build'?",
            );
        }

        $content = file_get_contents($manifestPath);
        if ($content === false) {
            throw new ManifestException('Failed to read manifest file: ' . $manifestPath);
        }

        try {
            $manifest = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new ManifestException(
                sprintf('Invalid JSON in manifest file: %s. Error: %s', $manifestPath, $jsonException->getMessage()),
            );
        }

        $collection = $this->parseManifest($manifest, $config->buildDirectory);

        // Cache the parsed manifest
        self::$manifestCache[$manifestPath] = $collection;

        return $collection;
    }

    /**
     * Clear the manifest cache
     *
     * Useful for testing to ensure cache isolation between tests.
     *
     * @internal
     */
    public static function clearCache(): void
    {
        self::$manifestCache = [];
    }

    /**
     * Resolve manifest path (supports plugins)
     *
     * @param \CakeVite\ValueObject\ViteConfig $config Configuration
     */
    private function resolveManifestPath(ViteConfig $config): string
    {
        // Application manifest (plugins not yet implemented)
        return $config->manifestPath;
    }

    /**
     * Parse manifest object into collection
     *
     * @param object $manifest Decoded manifest JSON
     * @param string|false $buildDirectory Build output directory
     */
    private function parseManifest(object $manifest, string|false $buildDirectory): ManifestCollection
    {
        $entries = [];

        foreach (get_object_vars($manifest) as $key => $data) {
            $entries[] = ManifestEntry::fromManifestData($key, $data, $buildDirectory ?: null);
        }

        return new ManifestCollection($entries);
    }
}
