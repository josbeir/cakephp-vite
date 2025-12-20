<?php
declare(strict_types=1);

namespace CakeVite\Service;

use CakeVite\Enum\AssetType;
use CakeVite\Exception\ConfigurationException;
use CakeVite\ValueObject\AssetTag;
use CakeVite\ValueObject\ViteConfig;

/**
 * Core service for generating asset tags
 *
 * Uses constructor property promotion. Services are manually instantiated
 * in helpers since DI container is not available in CakePHP helpers.
 */
final class AssetService
{
    /**
     * Constructor with property promotion
     *
     * @param \CakeVite\Service\EnvironmentService $environmentService Environment detection service
     * @param \CakeVite\Service\ManifestService $manifestService Manifest loading service
     */
    public function __construct(
        private readonly EnvironmentService $environmentService,
        private readonly ManifestService $manifestService,
    ) {
    }

    /**
     * Generate script tags
     *
     * @param \CakeVite\ValueObject\ViteConfig $config Configuration
     * @param array<string, mixed> $options Options (files, attributes, etc.)
     * @return array<\CakeVite\ValueObject\AssetTag>
     */
    public function generateScriptTags(ViteConfig $config, array $options = []): array
    {
        $env = $this->environmentService->detect($config);

        return $env->isDevelopment()
            ? $this->generateDevelopmentScriptTags($config, $options)
            : $this->generateProductionScriptTags($config, $options);
    }

    /**
     * Generate CSS tags
     *
     * @param \CakeVite\ValueObject\ViteConfig $config Configuration
     * @param array<string, mixed> $options Options (files, attributes, etc.)
     * @return array<\CakeVite\ValueObject\AssetTag>
     */
    public function generateStyleTags(ViteConfig $config, array $options = []): array
    {
        $env = $this->environmentService->detect($config);

        return $env->isDevelopment()
            ? $this->generateDevelopmentStyleTags($config, $options)
            : $this->generateProductionStyleTags($config, $options);
    }

    /**
     * Generate development mode script tags
     *
     * @param \CakeVite\ValueObject\ViteConfig $config Configuration
     * @param array<string, mixed> $options Options
     * @return array<\CakeVite\ValueObject\AssetTag>
     */
    private function generateDevelopmentScriptTags(ViteConfig $config, array $options): array
    {
        $tags = [];

        // Add Vite client
        $tags[] = new AssetTag(
            url: $config->devServerUrl . '/@vite/client',
            type: AssetType::Script,
            attributes: ['type' => 'module'],
        );

        // Add script entries
        $entries = $options['files'] ?? $options['devEntries'] ?? $config->scriptEntries;

        if (empty($entries)) {
            throw new ConfigurationException(
                'No script entries configured for development mode. ' .
                'Set "devServer.entries.script" in configuration or pass "files" option.',
            );
        }

        foreach ($entries as $entry) {
            $tags[] = new AssetTag(
                url: $config->devServerUrl . '/' . ltrim($entry, '/'),
                type: AssetType::Script,
                attributes: array_merge(['type' => 'module'], $options['attributes'] ?? []),
            );
        }

        return $tags;
    }

    /**
     * Generate production mode script tags
     *
     * @param \CakeVite\ValueObject\ViteConfig $config Configuration
     * @param array<string, mixed> $options Options
     * @return array<\CakeVite\ValueObject\AssetTag>
     */
    private function generateProductionScriptTags(ViteConfig $config, array $options): array
    {
        $manifest = $this->manifestService->load($config);

        // Apply filters if specified
        if (!empty($options['files'])) {
            $manifest = $manifest->filterByPattern($options['files']);
        } elseif (!empty($options['filter'])) {
            $manifest = $manifest->filterByPattern($options['filter']);
        }

        // Filter to script entries and sort by load order
        $entries = $manifest
            ->filterByType(AssetType::Script)
            ->filterEntries()
            ->sortByLoadOrder();

        $tags = [];
        $pluginPrefix = $config->pluginName ? $config->pluginName . '.' : '';

        foreach ($entries as $entry) {
            $scriptType = $entry->getScriptType();
            $attributes = $options['attributes'] ?? [];

            if ($scriptType?->isModule()) {
                $attributes['type'] = 'module';
            } elseif ($scriptType !== null) {
                $attributes['nomodule'] = true;
            }

            $tags[] = new AssetTag(
                url: $pluginPrefix . $entry->getUrl(),
                type: AssetType::Script,
                attributes: $attributes,
            );
        }

        return $tags;
    }

    /**
     * Generate development mode style tags
     *
     * @param \CakeVite\ValueObject\ViteConfig $config Configuration
     * @param array<string, mixed> $options Options
     * @return array<\CakeVite\ValueObject\AssetTag>
     */
    private function generateDevelopmentStyleTags(ViteConfig $config, array $options): array
    {
        $entries = $options['files'] ?? $options['devEntries'] ?? $config->styleEntries;

        $tags = [];
        foreach ($entries as $entry) {
            $tags[] = new AssetTag(
                url: $config->devServerUrl . '/' . ltrim($entry, '/'),
                type: AssetType::Style,
                attributes: $options['attributes'] ?? [],
            );
        }

        return $tags;
    }

    /**
     * Generate production mode style tags
     *
     * @param \CakeVite\ValueObject\ViteConfig $config Configuration
     * @param array<string, mixed> $options Options
     * @return array<\CakeVite\ValueObject\AssetTag>
     */
    private function generateProductionStyleTags(ViteConfig $config, array $options): array
    {
        $manifest = $this->manifestService->load($config);

        // Apply filters
        if (!empty($options['files'])) {
            $manifest = $manifest->filterByPattern($options['files']);
        } elseif (!empty($options['filter'])) {
            $manifest = $manifest->filterByPattern($options['filter']);
        }

        // Filter to style entries
        $entries = $manifest
            ->filterByType(AssetType::Style)
            ->filterEntries();

        $tags = [];
        $pluginPrefix = $config->pluginName ? $config->pluginName . '.' : '';

        foreach ($entries as $entry) {
            $tags[] = new AssetTag(
                url: $pluginPrefix . $entry->getUrl(),
                type: AssetType::Style,
                attributes: $options['attributes'] ?? [],
            );
        }

        return $tags;
    }
}
