<?php
declare(strict_types=1);

namespace CakeVite\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
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
     * Check if currently in development mode
     *
     * Backwards compatible with ViteScriptsHelper::isDev()
     *
     * @param \CakeVite\ValueObject\ViteConfig|array<string, mixed>|null $config Configuration
     */
    public function isDev(array|ViteConfig|null $config = null): bool
    {
        $config = $this->resolveConfig($config);
        $envService = new EnvironmentService($this->getView()->getRequest());

        return $envService->detect($config)->isDevelopment();
    }

    /**
     * Render script tags
     *
     * Backwards compatible with ViteScriptsHelper::script()
     *
     * @param array<string, mixed>|string $options Options or file shorthand
     * @param \CakeVite\ValueObject\ViteConfig|array<string, mixed>|null $config Configuration
     */
    public function script(array|string $options = [], array|ViteConfig|null $config = null): void
    {
        $config = $this->resolveConfig($config);
        $options = $this->normalizeOptions($options);

        $block = $options['block'] ?? $config->scriptBlock;
        $cssBlock = $options['cssBlock'] ?? $config->cssBlock;

        $tags = $this->getAssetService()->generateScriptTags($config, $options);

        // Render script tags
        foreach ($tags as $tag) {
            $this->Html->script($tag->url, array_merge(['block' => $block], $tag->attributes));
        }

        // Add dependent CSS if in production
        if (!$this->isDev($config)) {
            $this->addDependentCss($config, $options, $cssBlock);
        }
    }

    /**
     * Render CSS tags
     *
     * Backwards compatible with ViteScriptsHelper::css()
     *
     * @param array<string, mixed>|string $options Options or file shorthand
     * @param \CakeVite\ValueObject\ViteConfig|array<string, mixed>|null $config Configuration
     */
    public function css(array|string $options = [], array|ViteConfig|null $config = null): void
    {
        $config = $this->resolveConfig($config);
        $options = $this->normalizeOptions($options);

        $block = $options['block'] ?? $config->cssBlock;

        $tags = $this->getAssetService()->generateStyleTags($config, $options);

        foreach ($tags as $tag) {
            $this->Html->css($tag->url, array_merge(['block' => $block], $tag->attributes));
        }
    }

    /**
     * Convenience method for plugin scripts
     *
     * Backwards compatible with ViteScriptsHelper::pluginScript()
     *
     * @param string $pluginName Plugin name
     * @param bool $devMode Development mode flag
     * @param array<string, mixed> $options Options
     * @param \CakeVite\ValueObject\ViteConfig|array<string, mixed>|null $config Configuration
     */
    public function pluginScript(
        string $pluginName,
        bool $devMode = false,
        array $options = [],
        array|ViteConfig|null $config = null,
    ): void {
        $config = $this->resolveConfig($config);
        $config = $config->merge([
            'plugin' => $pluginName,
            'forceProductionMode' => !$devMode,
        ]);

        $this->script($options, $config);
    }

    /**
     * Convenience method for plugin styles
     *
     * Backwards compatible with ViteScriptsHelper::pluginCss()
     *
     * @param string $pluginName Plugin name
     * @param bool $devMode Development mode flag
     * @param array<string, mixed> $options Options
     * @param \CakeVite\ValueObject\ViteConfig|array<string, mixed>|null $config Configuration
     */
    public function pluginCss(
        string $pluginName,
        bool $devMode = false,
        array $options = [],
        array|ViteConfig|null $config = null,
    ): void {
        $config = $this->resolveConfig($config);
        $config = $config->merge([
            'plugin' => $pluginName,
            'forceProductionMode' => !$devMode,
        ]);

        $this->css($options, $config);
    }

    /**
     * Resolve configuration from various input types
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

        // Load from Configure
        return ViteConfig::fromArray(Configure::read('CakeVite', []));
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
     * Add dependent CSS from JS entries
     *
     * @param \CakeVite\ValueObject\ViteConfig $config Configuration
     * @param array<string, mixed> $options Options
     * @param string $cssBlock CSS block name
     */
    private function addDependentCss(ViteConfig $config, array $options, string $cssBlock): void
    {
        $manifestService = new ManifestService();
        $manifest = $manifestService->load($config);

        // Apply same filters as scripts
        if (!empty($options['filter'])) {
            $manifest = $manifest->filterByPattern($options['filter']);
        }

        $entries = $manifest->filterEntries();
        $pluginPrefix = $config->pluginName ? $config->pluginName . '.' : '';

        foreach ($entries as $entry) {
            foreach ($entry->getDependentCssUrls() as $cssUrl) {
                $this->Html->css($pluginPrefix . $cssUrl, ['block' => $cssBlock]);
            }
        }
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
