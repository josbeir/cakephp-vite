<?php
declare(strict_types=1);

namespace CakeVite\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
use CakeVite\Enum\Environment;
use CakeVite\Service\AssetService;
use CakeVite\Service\EnvironmentService;
use CakeVite\Service\ManifestService;
use CakeVite\ValueObject\ViteConfig;

/**
 * Vite Helper - Backwards compatible with ViteScriptsHelper
 *
 * Services are manually instantiated since CakePHP's DI container
 * is not available in helpers (only in Controllers, Commands, and Components).
 *
 * @extends \Cake\View\Helper<\Cake\View\View>
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class ViteHelper extends Helper
{
    /**
     * Helpers used by this helper
     *
     * @var array<string>
     */
    protected array $helpers = ['Html'];

    /**
     * Lazy-loaded asset service (manually instantiated)
     */
    private ?AssetService $assetService = null;

    /**
     * Cached configuration (optimization #1: reduce Configure::read calls)
     */
    private ?ViteConfig $cachedConfig = null;

    /**
     * Cached environment detection (optimization #2: reduce environment detection overhead)
     */
    private ?Environment $cachedEnvironment = null;

    /**
     * Track if @vite/client has been rendered (optimization #3: deduplicate vite client)
     */
    private bool $viteClientRendered = false;

    /**
     * Check if currently in development mode
     *
     * Backwards compatible with ViteScriptsHelper::isDev()
     *
     * Uses cached environment detection to avoid repeated cookie/query/host checks.
     *
     * @param \CakeVite\ValueObject\ViteConfig|array<string, mixed>|null $config Configuration
     */
    public function isDev(array|ViteConfig|null $config = null): bool
    {
        // Return cached result if available (optimization #2)
        if ($this->cachedEnvironment instanceof Environment && $config === null) {
            return $this->cachedEnvironment->isDevelopment();
        }

        $config = $this->resolveConfig($config);
        $envService = new EnvironmentService($this->getView()->getRequest());
        $environment = $envService->detect($config);

        // Cache environment for subsequent calls (only when using default config)
        if ($config === $this->cachedConfig) {
            $this->cachedEnvironment = $environment;
        }

        return $environment->isDevelopment();
    }

    /**
     * Render script tags
     *
     * Backwards compatible with ViteScriptsHelper::script()
     *
     * When `block` option is `false`, returns the tags as a string for inline output.
     * Otherwise appends to the specified view block and returns null.
     *
     * @param array<string, mixed>|string $options Options or file shorthand
     * @param \CakeVite\ValueObject\ViteConfig|array<string, mixed>|null $config Configuration
     * @return string|null Returns tag string when block is false, null otherwise
     */
    public function script(array|string $options = [], array|ViteConfig|null $config = null): ?string
    {
        $options = $this->normalizeOptions($options);
        $config = $this->extractConfigFromOptions($options, $config);

        // Handle preload option override
        if (isset($options['preload'])) {
            $config = $this->mergePreloadOption($config, $options['preload']);
            unset($options['preload']); // Remove from options after merging into config
        }

        $config = $this->resolveConfig($config);

        $block = $options['block'] ?? $config->scriptBlock;
        $cssBlock = $options['cssBlock'] ?? $config->cssBlock;
        $inline = $block === false;

        // Pass flag to skip vite client if already rendered (deduplication)
        $options['skipViteClient'] = $this->viteClientRendered;
        $tags = $this->getAssetService()->generateScriptTags($config, $options);

        // Mark vite client as rendered after first dev mode script() call
        if ($this->isDev($config) && !$this->viteClientRendered) {
            $this->viteClientRendered = true;
        }

        $output = '';

        // Render tags (preload tags as link elements, script tags as script elements)
        foreach ($tags as $tag) {
            if ($tag->isPreload) {
                // Render preload tags as <link rel="modulepreload" href="...">
                $preloadType = $tag->preloadType ?? 'modulepreload';
                $linkTag = $this->buildPreloadLinkTag($preloadType, $tag->url, $tag->attributes);
                if ($inline) {
                    $output .= $linkTag;
                } else {
                    $this->getView()->append($block, $linkTag);
                }
            } else {
                // Render regular script tags
                // When block is false, Html::script() returns the tag string
                $scriptTag = $this->Html->script($tag->url, array_merge(['block' => $block], $tag->attributes));
                if ($inline && $scriptTag !== null) {
                    $output .= $scriptTag;
                }
            }
        }

        // Add dependent CSS if in production
        if (!$this->isDev($config)) {
            $output .= $this->addDependentCss($config, $options, $cssBlock, $inline);
        }

        return $inline ? $output : null;
    }

    /**
     * Render CSS tags
     *
     * Backwards compatible with ViteScriptsHelper::css()
     *
     * When `block` option is `false`, returns the tags as a string for inline output.
     * Otherwise appends to the specified view block and returns null.
     *
     * @param array<string, mixed>|string $options Options or file shorthand
     * @param \CakeVite\ValueObject\ViteConfig|array<string, mixed>|null $config Configuration
     * @return string|null Returns tag string when block is false, null otherwise
     */
    public function css(array|string $options = [], array|ViteConfig|null $config = null): ?string
    {
        $options = $this->normalizeOptions($options);
        $config = $this->extractConfigFromOptions($options, $config);

        $config = $this->resolveConfig($config);

        $block = $options['block'] ?? $config->cssBlock;
        $inline = $block === false;

        $tags = $this->getAssetService()->generateStyleTags($config, $options);

        $output = '';

        foreach ($tags as $tag) {
            // When block is false, Html::css() returns the tag string
            $cssTag = $this->Html->css($tag->url, array_merge(['block' => $block], $tag->attributes));
            if ($inline && $cssTag !== null) {
                $output .= $cssTag;
            }
        }

        return $inline ? $output : null;
    }

    /**
     * Convenience method for plugin scripts
     *
     * Backwards compatible with ViteScriptsHelper::pluginScript()
     *
     * When `block` option is `false`, returns the tags as a string for inline output.
     * Otherwise appends to the specified view block and returns null.
     *
     * @param string $pluginName Plugin name
     * @param bool $devMode Development mode flag
     * @param array<string, mixed> $options Options
     * @param \CakeVite\ValueObject\ViteConfig|array<string, mixed>|null $config Configuration
     * @return string|null Returns tag string when block is false, null otherwise
     */
    public function pluginScript(
        string $pluginName,
        bool $devMode = false,
        array $options = [],
        array|ViteConfig|null $config = null,
    ): ?string {
        $config = $this->resolveConfig($config);
        $config = $config->merge([
            'plugin' => $pluginName,
            'forceProductionMode' => !$devMode,
        ]);

        return $this->script($options, $config);
    }

    /**
     * Convenience method for plugin styles
     *
     * Backwards compatible with ViteScriptsHelper::pluginCss()
     *
     * When `block` option is `false`, returns the tags as a string for inline output.
     * Otherwise appends to the specified view block and returns null.
     *
     * @param string $pluginName Plugin name
     * @param bool $devMode Development mode flag
     * @param array<string, mixed> $options Options
     * @param \CakeVite\ValueObject\ViteConfig|array<string, mixed>|null $config Configuration
     * @return string|null Returns tag string when block is false, null otherwise
     */
    public function pluginCss(
        string $pluginName,
        bool $devMode = false,
        array $options = [],
        array|ViteConfig|null $config = null,
    ): ?string {
        $config = $this->resolveConfig($config);
        $config = $config->merge([
            'plugin' => $pluginName,
            'forceProductionMode' => !$devMode,
        ]);

        return $this->css($options, $config);
    }

    /**
     * Resolve configuration from various input types
     *
     * Uses cached configuration to reduce Configure::read() overhead (optimization #1).
     *
     * @param \CakeVite\ValueObject\ViteConfig|array<string, mixed>|null $config Configuration
     */
    private function resolveConfig(array|ViteConfig|null $config): ViteConfig
    {
        if ($config instanceof ViteConfig) {
            return $config;
        }

        if (is_array($config)) {
            return ViteConfig::fromArray($config);
        }

        // Load from Configure and cache for request lifetime (optimization #1)
        return $this->cachedConfig ??= ViteConfig::fromArray(Configure::read('CakeVite', []));
    }

    /**
     * Normalize options (handle string shorthand)
     *
     * @param array<string, mixed>|string $options Options
     * @return array<string, mixed>
     */
    private function normalizeOptions(array|string $options): array
    {
        if (is_string($options)) {
            return ['files' => [$options]];
        }

        // Backwards compatibility: convert prodFilter to filter
        if (isset($options['prodFilter']) && !isset($options['filter'])) {
            $options['filter'] = $options['prodFilter'];
            unset($options['prodFilter']);
        }

        // Handle 'files' option
        if (isset($options['files'])) {
            $options['filter'] = $options['files'];
        }

        return $options;
    }

    /**
     * Extract named config from options
     *
     * @param array<string, mixed> $options Options (modified by reference to remove 'config' key)
     * @param \CakeVite\ValueObject\ViteConfig|array<string, mixed>|null $config Configuration
     * @return \CakeVite\ValueObject\ViteConfig|array<string, mixed>|null Modified configuration
     */
    private function extractConfigFromOptions(
        array &$options,
        array|ViteConfig|null $config,
    ): array|ViteConfig|null {
        if (!isset($options['config']) || !is_string($options['config'])) {
            return $config;
        }

        $configName = $options['config'];
        unset($options['config']);

        $config = $config ?? [];
        if (is_array($config)) {
            $config['config'] = $configName;
        } else {
            // If config is ViteConfig object, load named config from Configure
            $config = ViteConfig::fromArray([], $configName);
        }

        return $config;
    }

    /**
     * Add dependent CSS from JS entries
     *
     * When cssBlock is false and inline mode is disabled, dependent CSS injection is skipped.
     * This allows users to explicitly disable automatic CSS injection.
     *
     * @param \CakeVite\ValueObject\ViteConfig $config Configuration
     * @param array<string, mixed> $options Options
     * @param string|false $cssBlock CSS block name or false to disable/inline
     * @param bool $inline Whether to return inline (when script block is false)
     * @return string Returns CSS tags when inline is true, empty string otherwise
     */
    private function addDependentCss(
        ViteConfig $config,
        array $options,
        string|false $cssBlock,
        bool $inline = false,
    ): string {
        // Skip dependent CSS injection if cssBlock is false and not in inline mode
        if ($cssBlock === false && !$inline) {
            return '';
        }

        $manifestService = new ManifestService();
        $manifest = $manifestService->load($config);

        // Apply same filters as scripts
        if (!empty($options['filter'])) {
            $manifest = $manifest->filterByPattern($options['filter']);
        }

        $entries = $manifest->filterEntries();
        $pluginPrefix = $config->pluginName ? $config->pluginName . '.' : '';

        $output = '';

        foreach ($entries as $entry) {
            foreach ($entry->getDependentCssUrls() as $cssUrl) {
                // When inline, pass block => false to get the tag string; otherwise use the block name
                $blockOption = $inline ? false : $cssBlock;
                $cssTag = $this->Html->css($pluginPrefix . $cssUrl, ['block' => $blockOption]);
                if ($inline && $cssTag !== null) {
                    $output .= $cssTag;
                }
            }
        }

        return $output;
    }

    /**
     * Merge preload option into configuration
     *
     * @param \CakeVite\ValueObject\ViteConfig|array<string, mixed>|null $config Configuration
     * @param string $preloadMode Preload mode (none, link-tag, link-header)
     * @return \CakeVite\ValueObject\ViteConfig|array<string, mixed>
     */
    private function mergePreloadOption(
        array|ViteConfig|null $config,
        string $preloadMode,
    ): array|ViteConfig {
        if ($config instanceof ViteConfig) {
            return $config->merge(['preload' => $preloadMode]);
        }

        if (is_array($config)) {
            $config['preload'] = $preloadMode;

            return $config;
        }

        // Null config - return array with preload option
        return ['preload' => $preloadMode];
    }

    /**
     * Build preload link tag with proper attribute rendering
     *
     * Supports attributes like crossorigin, integrity, as, etc.
     * Filters out 'rel' attribute to avoid conflicts with preloadType.
     *
     * @param string $preloadType Preload type (modulepreload, preload, etc.)
     * @param string $url Asset URL
     * @param array<string, mixed> $attributes HTML attributes
     * @return string Complete link tag HTML
     */
    private function buildPreloadLinkTag(string $preloadType, string $url, array $attributes): string
    {
        // Filter out 'rel' attribute to avoid conflicts
        $attributes = array_filter(
            $attributes,
            fn($key): bool => $key !== 'rel',
            ARRAY_FILTER_USE_KEY,
        );

        // Build attribute string
        $attributesString = '';
        foreach ($attributes as $attrName => $attrValue) {
            if ($attrValue === null) {
                continue;
            }

            // Handle boolean attributes (e.g., async, defer)
            if (is_bool($attrValue)) {
                if ($attrValue) {
                    $attributesString .= ' ' . h((string)$attrName);
                }

                continue;
            }

            // Regular attributes with values
            $attributesString .= sprintf(
                ' %s="%s"',
                h((string)$attrName),
                h((string)$attrValue),
            );
        }

        return sprintf(
            '<link rel="%s" href="%s"%s>',
            h($preloadType),
            h($url),
            $attributesString,
        );
    }

    /**
     * Get or create asset service
     *
     * Services are manually instantiated since DI container is not available in helpers.
     * This is lazy-loaded to avoid unnecessary instantiation.
     */
    private function getAssetService(): AssetService
    {
        if (!$this->assetService instanceof AssetService) {
            $this->assetService = new AssetService(
                new EnvironmentService($this->getView()->getRequest()),
                new ManifestService(),
            );
        }

        return $this->assetService;
    }
}
