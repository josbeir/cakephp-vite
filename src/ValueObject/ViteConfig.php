<?php
declare(strict_types=1);

namespace CakeVite\ValueObject;

use Cake\Core\Configure;
use CakeVite\Enum\PreloadMode;

/**
 * Immutable configuration value object
 *
 * Uses constructor property promotion and readonly for immutability.
 */
final readonly class ViteConfig
{
    /**
     * Constructor with property promotion
     *
     * @param string $devServerUrl Development server URL
     * @param array<string> $devServerHostHints Host hints for development detection
     * @param array<string> $scriptEntries Script entry files for development
     * @param array<string> $styleEntries Style entry files for development
     * @param string $manifestPath Path to manifest.json file
     * @param string|false $buildDirectory Build output directory (false for root)
     * @param bool $forceProductionMode Force production mode regardless of environment
     * @param string|null $pluginName Plugin name if assets belong to plugin
     * @param string $productionModeHint Cookie/query param name for production mode hint
     * @param string $scriptBlock View block name for scripts
     * @param string $cssBlock View block name for CSS
     * @param \CakeVite\Enum\PreloadMode $preloadMode Preload mode for module dependencies
     * @param string|false $cacheConfig CakePHP cache config name (false = disabled)
     * @param bool $cacheInDevelopment Enable caching in development mode
     */
    public function __construct(
        public string $devServerUrl,
        public array $devServerHostHints,
        public array $scriptEntries,
        public array $styleEntries,
        public string $manifestPath,
        public string|false $buildDirectory,
        public bool $forceProductionMode,
        public ?string $pluginName,
        public string $productionModeHint,
        public string $scriptBlock,
        public string $cssBlock,
        public PreloadMode $preloadMode,
        public string|false $cacheConfig,
        public bool $cacheInDevelopment,
    ) {
    }

    /**
     * Create from array configuration with named config support
     *
     * Uses ?? operator for efficient default values instead of Hash::get.
     * Supports named configurations that inherit from default config.
     *
     * @param array<string, mixed> $config Configuration array
     * @param string|null $configName Optional named config to load from Configure::read('CakeVite.configs.{name}')
     */
    public static function fromArray(array $config, ?string $configName = null): self
    {
        // Handle 'config' key in array (for helper option)
        if (isset($config['config']) && is_string($config['config'])) {
            $configName = $config['config'];
            unset($config['config']);
        }

        // Load named config if specified
        if ($configName !== null) {
            $config = self::loadNamedConfig($configName, $config);
        }

        return new self(
            devServerUrl: $config['devServer']['url'] ?? 'http://localhost:3000',
            devServerHostHints: $config['devServer']['hostHints'] ?? ['localhost', '127.0.0.1', '.test', '.local'],
            scriptEntries: $config['devServer']['entries']['script'] ?? [],
            styleEntries: $config['devServer']['entries']['style'] ?? [],
            manifestPath: $config['build']['manifestPath'] ?? WWW_ROOT . 'manifest.json',
            buildDirectory: $config['build']['outDirectory'] ?? false,
            forceProductionMode: $config['forceProductionMode'] ?? false,
            pluginName: $config['plugin'] ?? null,
            productionModeHint: $config['productionModeHint'] ?? 'vprod',
            scriptBlock: $config['viewBlocks']['script'] ?? 'script',
            cssBlock: $config['viewBlocks']['css'] ?? 'css',
            preloadMode: isset($config['preload']) ? PreloadMode::from($config['preload']) : PreloadMode::LinkTag,
            cacheConfig: $config['cache']['config'] ?? false,
            cacheInDevelopment: $config['cache']['development'] ?? false,
        );
    }

    /**
     * Load named configuration with inheritance from default
     *
     * @param string $configName Name of the configuration
     * @param array<string, mixed> $overrides Additional overrides to apply
     * @return array<string, mixed> Merged configuration
     */
    private static function loadNamedConfig(string $configName, array $overrides = []): array
    {
        // Load default config from Configure
        $defaultConfig = Configure::read('CakeVite', []);

        // Load named config
        $namedConfig = $defaultConfig['configs'][$configName] ?? [];

        // Remove 'configs' key from default to avoid recursion
        unset($defaultConfig['configs']);

        // Merge: default -> named -> overrides
        $merged = self::arrayMergeRecursiveDistinct($defaultConfig, $namedConfig);

        return self::arrayMergeRecursiveDistinct($merged, $overrides);
    }

    /**
     * Recursively merge arrays without converting non-numeric keys to arrays
     *
     * Unlike array_merge_recursive, this preserves array values instead of
     * converting them to nested arrays.
     *
     * @param array<string, mixed> $array1 Base array
     * @param array<string, mixed> $array2 Array to merge
     * @return array<string, mixed> Merged array
     */
    private static function arrayMergeRecursiveDistinct(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Merge with another config (returns new instance)
     *
     * @param array<string, mixed> $overrides Configuration overrides
     */
    public function merge(array $overrides): self
    {
        $current = $this->toArray();

        // Manually merge to avoid array_merge_recursive issues with nested arrays
        foreach ($overrides as $key => $value) {
            if (isset($current[$key]) && is_array($current[$key]) && is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    if (isset($current[$key][$subKey]) && is_array($current[$key][$subKey]) && is_array($subValue)) {
                        $current[$key][$subKey] = array_merge($current[$key][$subKey], $subValue);
                    } else {
                        $current[$key][$subKey] = $subValue;
                    }
                }
            } else {
                $current[$key] = $value;
            }
        }

        return self::fromArray($current);
    }

    /**
     * Convert to array for merging
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'devServer' => [
                'url' => $this->devServerUrl,
                'hostHints' => $this->devServerHostHints,
                'entries' => [
                    'script' => $this->scriptEntries,
                    'style' => $this->styleEntries,
                ],
            ],
            'build' => [
                'manifestPath' => $this->manifestPath,
                'outDirectory' => $this->buildDirectory,
            ],
            'forceProductionMode' => $this->forceProductionMode,
            'plugin' => $this->pluginName,
            'productionModeHint' => $this->productionModeHint,
            'viewBlocks' => [
                'script' => $this->scriptBlock,
                'css' => $this->cssBlock,
            ],
            'preload' => $this->preloadMode->value,
            'cache' => [
                'config' => $this->cacheConfig,
                'development' => $this->cacheInDevelopment,
            ],
        ];
    }
}
