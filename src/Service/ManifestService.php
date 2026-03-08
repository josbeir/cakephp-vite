<?php
declare(strict_types=1);

namespace CakeVite\Service;

use Cake\Cache\Cache;
use CakeVite\Enum\Environment;
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
     * Load and parse manifest file with persistent caching
     *
     * Supports both in-memory (per-request) and persistent caching via CakePHP Cache.
     * Cache key includes manifest path and mtime for automatic invalidation.
     *
     * @param \CakeVite\ValueObject\ViteConfig $config Configuration
     * @param \CakeVite\Enum\Environment $env Current environment (dev/production)
     * @return \CakeVite\ValueObject\ManifestCollection Collection of manifest entries
     * @throws \CakeVite\Exception\ManifestException
     */
    public function load(ViteConfig $config, Environment $env = Environment::Production): ManifestCollection
    {
        $manifestPath = $this->resolveManifestPath($config);

        // Check in-memory cache first (fastest)
        if (isset(self::$manifestCache[$manifestPath])) {
            return self::$manifestCache[$manifestPath];
        }

        // Check if persistent caching is enabled
        if ($config->cacheConfig && ($env->isProduction() || $config->cacheInDevelopment)) {
            $cacheKey = $this->getCacheKey($config, $manifestPath);

            $collection = Cache::remember($cacheKey, function () use ($manifestPath, $config): ManifestCollection {
                return $this->loadFromFile($manifestPath, $config->buildDirectory);
            }, $config->cacheConfig);

            // Also store in memory cache for this request
            self::$manifestCache[$manifestPath] = $collection;

            return $collection;
        }

        // No caching - load directly
        $collection = $this->loadFromFile($manifestPath, $config->buildDirectory);

        // Store in memory cache for this request
        self::$manifestCache[$manifestPath] = $collection;

        return $collection;
    }

    /**
     * Generate cache key with mtime for automatic invalidation
     *
     * @param \CakeVite\ValueObject\ViteConfig $config Configuration
     * @param string $manifestPath Path to manifest file
     * @return string Cache key
     */
    private function getCacheKey(ViteConfig $config, string $manifestPath): string
    {
        $prefix = $config->pluginName ? $config->pluginName . '_' : '';
        $mtime = file_exists($manifestPath) ? filemtime($manifestPath) : 0;

        return $prefix . 'vite_manifest_' . md5($manifestPath) . '_' . $mtime;
    }

    /**
     * Load manifest from file
     *
     * @param string $manifestPath Path to manifest file
     * @param string|false $buildDirectory Build output directory
     * @throws \CakeVite\Exception\ManifestException
     */
    private function loadFromFile(string $manifestPath, string|false $buildDirectory): ManifestCollection
    {
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
                $jsonException->getCode(),
                $jsonException,
            );
        }

        return $this->parseManifest($manifest, $buildDirectory);
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
