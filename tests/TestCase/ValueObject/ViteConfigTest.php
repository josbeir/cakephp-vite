<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase\ValueObject;

use Cake\Core\Configure;
use CakeVite\Enum\PreloadMode;
use CakeVite\ValueObject\ViteConfig;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * ViteConfig Value Object Test
 *
 * Following TDD principles - this test is written BEFORE the value object exists.
 */
class ViteConfigTest extends TestCase
{
    /**
     * Test creating ViteConfig from array with all values
     */
    public function testFromArrayWithAllValues(): void
    {
        $config = ViteConfig::fromArray([
            'devServer' => [
                'url' => 'http://localhost:5173',
                'hostHints' => ['localhost', '.test'],
                'entries' => [
                    'script' => ['main.ts', 'app.ts'],
                    'style' => ['style.css'],
                ],
            ],
            'build' => [
                'manifestPath' => '/path/to/manifest.json',
                'outDirectory' => 'dist',
            ],
            'forceProductionMode' => true,
            'plugin' => 'MyPlugin',
            'productionModeHint' => 'prod',
            'viewBlocks' => [
                'script' => 'js',
                'css' => 'styles',
            ],
            'preload' => 'none',
            'cache' => [
                'config' => 'vite_cache',
                'development' => true,
            ],
        ]);

        $this->assertSame('http://localhost:5173', $config->devServerUrl);
        $this->assertSame(['localhost', '.test'], $config->devServerHostHints);
        $this->assertSame(['main.ts', 'app.ts'], $config->scriptEntries);
        $this->assertSame(['style.css'], $config->styleEntries);
        $this->assertSame('/path/to/manifest.json', $config->manifestPath);
        $this->assertSame('dist', $config->buildDirectory);
        $this->assertTrue($config->forceProductionMode);
        $this->assertSame('MyPlugin', $config->pluginName);
        $this->assertSame('prod', $config->productionModeHint);
        $this->assertSame('js', $config->scriptBlock);
        $this->assertSame('styles', $config->cssBlock);
        $this->assertSame(PreloadMode::None, $config->preloadMode);
        $this->assertSame('vite_cache', $config->cacheConfig);
        $this->assertTrue($config->cacheInDevelopment);
    }

    /**
     * Test creating ViteConfig with default values
     */
    public function testFromArrayWithDefaults(): void
    {
        $config = ViteConfig::fromArray([]);

        $this->assertSame('http://localhost:3000', $config->devServerUrl);
        $this->assertSame(['localhost', '127.0.0.1', '.test', '.local'], $config->devServerHostHints);
        $this->assertSame([], $config->scriptEntries);
        $this->assertSame([], $config->styleEntries);
        $this->assertStringContainsString('manifest.json', $config->manifestPath);
        $this->assertFalse($config->buildDirectory);
        $this->assertFalse($config->forceProductionMode);
        $this->assertNull($config->pluginName);
        $this->assertSame('vprod', $config->productionModeHint);
        $this->assertSame('script', $config->scriptBlock);
        $this->assertSame('css', $config->cssBlock);
        $this->assertSame(PreloadMode::LinkTag, $config->preloadMode);
        $this->assertFalse($config->cacheConfig);
        $this->assertFalse($config->cacheInDevelopment);
    }

    /**
     * Test ViteConfig is readonly (immutable)
     */
    public function testViteConfigIsReadonly(): void
    {
        $config = ViteConfig::fromArray(['devServer' => ['url' => 'http://test']]);

        $reflection = new ReflectionClass($config);
        $this->assertTrue($reflection->isReadOnly());
    }

    /**
     * Test merge returns new instance
     */
    public function testMergeReturnsNewInstance(): void
    {
        $config1 = ViteConfig::fromArray([
            'devServer' => ['url' => 'http://localhost:3000'],
        ]);

        $config2 = $config1->merge([
            'devServer' => ['url' => 'http://localhost:5173'],
        ]);

        $this->assertNotSame($config1, $config2);
        $this->assertSame('http://localhost:3000', $config1->devServerUrl);
        $this->assertSame('http://localhost:5173', $config2->devServerUrl);
    }

    /**
     * Test toArray converts back to array structure
     */
    public function testToArrayConvertsToArrayStructure(): void
    {
        $original = [
            'devServer' => [
                'url' => 'http://localhost:5173',
                'hostHints' => ['localhost'],
                'entries' => [
                    'script' => ['main.ts'],
                    'style' => ['style.css'],
                ],
            ],
            'build' => [
                'manifestPath' => '/path/to/manifest.json',
                'outDirectory' => 'dist',
            ],
            'forceProductionMode' => true,
            'plugin' => 'MyPlugin',
            'productionModeHint' => 'prod',
            'viewBlocks' => [
                'script' => 'js',
                'css' => 'styles',
            ],
        ];

        $config = ViteConfig::fromArray($original);
        $result = $config->toArray();

        $this->assertSame($original['devServer']['url'], $result['devServer']['url']);
        $this->assertSame($original['devServer']['hostHints'], $result['devServer']['hostHints']);
        $this->assertSame($original['devServer']['entries']['script'], $result['devServer']['entries']['script']);
        $this->assertSame($original['build']['manifestPath'], $result['build']['manifestPath']);
        $this->assertSame($original['forceProductionMode'], $result['forceProductionMode']);
    }

    /**
     * Test partial config arrays are handled correctly
     */
    public function testPartialConfigArrays(): void
    {
        $config = ViteConfig::fromArray([
            'devServer' => [
                'url' => 'http://custom:8080',
            ],
            'plugin' => 'TestPlugin',
        ]);

        $this->assertSame('http://custom:8080', $config->devServerUrl);
        $this->assertSame('TestPlugin', $config->pluginName);
        // Defaults should still apply
        $this->assertSame(['localhost', '127.0.0.1', '.test', '.local'], $config->devServerHostHints);
        $this->assertFalse($config->forceProductionMode);
    }

    /**
     * Test loading named config with inheritance from default
     */
    public function testFromArrayWithNamedConfig(): void
    {
        Configure::write('CakeVite', [
            'devServer' => [
                'url' => 'http://localhost:3000',
                'entries' => ['script' => ['src/main.js']],
            ],
            'build' => [
                'manifestPath' => WWW_ROOT . 'manifest.json',
            ],
            'configs' => [
                'admin' => [
                    'devServer' => [
                        'url' => 'http://localhost:3001',
                        'entries' => ['script' => ['admin/main.js']],
                    ],
                    'build' => [
                        'outDirectory' => 'admin',
                    ],
                ],
            ],
        ]);

        $config = ViteConfig::fromArray([], 'admin');

        // Should inherit default URL is overridden
        $this->assertSame('http://localhost:3001', $config->devServerUrl);
        // Should inherit default manifest path (not overridden in named config)
        $this->assertStringContainsString('manifest.json', $config->manifestPath);
        // Should use named config's outDirectory
        $this->assertSame('admin', $config->buildDirectory);
        // Should use named config's entries
        $this->assertSame(['admin/main.js'], $config->scriptEntries);
    }

    /**
     * Test loading non-existent named config falls back to default
     */
    public function testFromArrayWithNonExistentNamedConfig(): void
    {
        Configure::write('CakeVite', [
            'devServer' => [
                'url' => 'http://localhost:3000',
            ],
        ]);

        $config = ViteConfig::fromArray([], 'nonexistent');

        // Should use default config
        $this->assertSame('http://localhost:3000', $config->devServerUrl);
    }

    /**
     * Test named config can be passed directly in array
     */
    public function testFromArrayWithConfigKeyInArray(): void
    {
        Configure::write('CakeVite', [
            'devServer' => [
                'url' => 'http://localhost:3000',
            ],
            'configs' => [
                'marketing' => [
                    'devServer' => [
                        'url' => 'http://localhost:3002',
                    ],
                ],
            ],
        ]);

        $config = ViteConfig::fromArray(['config' => 'marketing']);

        $this->assertSame('http://localhost:3002', $config->devServerUrl);
    }
}
