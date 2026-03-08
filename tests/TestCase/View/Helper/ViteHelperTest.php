<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use CakeVite\Enum\Environment;
use CakeVite\Service\ManifestService;
use CakeVite\ValueObject\ViteConfig;
use CakeVite\View\Helper\ViteHelper;
use ReflectionClass;

/**
 * ViteHelper Test
 *
 * Following TDD principles - this test is written BEFORE the helper exists.
 */
class ViteHelperTest extends TestCase
{
    protected ViteHelper $Vite;

    protected View $View;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->View = new View(new ServerRequest([
            'environment' => ['HTTP_HOST' => 'localhost'],
        ]));
        $this->Vite = new ViteHelper($this->View);
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        ManifestService::clearCache();
    }

    /**
     * Test isDev returns true for development environment
     */
    public function testIsDevReturnsTrueForDevelopment(): void
    {
        $config = [
            'devServer' => ['hostHints' => ['localhost']],
        ];

        $result = $this->Vite->isDev($config);

        $this->assertTrue($result);
    }

    /**
     * Test isDev returns false for production environment
     */
    public function testIsDevReturnsFalseForProduction(): void
    {
        $config = [
            'forceProductionMode' => true,
        ];

        $result = $this->Vite->isDev($config);

        $this->assertFalse($result);
    }

    /**
     * Test script method adds scripts to view block in development
     */
    public function testScriptAddsToViewBlockInDevelopment(): void
    {
        $config = [
            'devServer' => [
                'url' => 'http://localhost:3000',
                'hostHints' => ['localhost'],
                'entries' => ['script' => ['src/app.ts']],
            ],
        ];

        $this->Vite->script(['files' => ['src/app.ts']], $config);

        $result = $this->View->fetch('script');
        $this->assertStringContainsString('http://localhost:3000/@vite/client', $result);
        $this->assertStringContainsString('http://localhost:3000/src/app.ts', $result);
        $this->assertStringContainsString('type="module"', $result);
    }

    /**
     * Test script method with string shorthand
     */
    public function testScriptAcceptsStringShorthand(): void
    {
        $config = [
            'devServer' => [
                'url' => 'http://localhost:3000',
                'hostHints' => ['localhost'],
                'entries' => ['script' => ['src/app.ts']],
            ],
        ];

        $this->Vite->script('src/app.ts', $config);

        $result = $this->View->fetch('script');
        $this->assertStringContainsString('http://localhost:3000/src/app.ts', $result);
    }

    /**
     * Test script method uses manifest in production
     */
    public function testScriptUsesManifestInProduction(): void
    {
        $config = [
            'forceProductionMode' => true,
            'build' => [
                'manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json',
            ],
        ];

        $this->Vite->script(['files' => ['src/app.ts']], $config);

        $result = $this->View->fetch('script');
        $this->assertStringContainsString('assets/app-abc123.js', $result);
        $this->assertStringNotContainsString('localhost', $result);
    }

    /**
     * Test script method adds dependent CSS in production
     */
    public function testScriptAddsDependentCssInProduction(): void
    {
        $config = [
            'forceProductionMode' => true,
            'build' => [
                'manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json',
            ],
        ];

        $this->Vite->script(['files' => ['src/app.ts']], $config);

        $cssResult = $this->View->fetch('css');
        $this->assertStringContainsString('assets/app-xyz789.css', $cssResult);
    }

    /**
     * Test css method adds styles to view block in development
     */
    public function testCssAddsToViewBlockInDevelopment(): void
    {
        $config = [
            'devServer' => [
                'url' => 'http://localhost:3000',
                'hostHints' => ['localhost'],
                'entries' => ['style' => ['src/style.css']],
            ],
        ];

        $this->Vite->css(['files' => ['src/style.css']], $config);

        $result = $this->View->fetch('css');
        $this->assertStringContainsString('http://localhost:3000/src/style.css', $result);
    }

    /**
     * Test css method uses manifest in production
     */
    public function testCssUsesManifestInProduction(): void
    {
        $config = [
            'forceProductionMode' => true,
            'build' => [
                'manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json',
            ],
        ];

        $this->Vite->css(['files' => ['src/style.css']], $config);

        $result = $this->View->fetch('css');
        $this->assertStringContainsString('assets/style-jkl012.css', $result);
    }

    /**
     * Test custom view block names
     */
    public function testCustomViewBlocks(): void
    {
        $config = [
            'devServer' => [
                'url' => 'http://localhost:3000',
                'hostHints' => ['localhost'],
                'entries' => ['script' => ['src/app.ts']],
            ],
            'viewBlocks' => [
                'script' => 'js',
                'css' => 'styles',
            ],
        ];

        $this->Vite->script(['files' => ['src/app.ts']], $config);

        $result = $this->View->fetch('js');
        $this->assertStringContainsString('src/app.ts', $result);
    }

    /**
     * Test block option overrides default
     */
    public function testBlockOptionOverridesDefault(): void
    {
        $config = [
            'devServer' => [
                'url' => 'http://localhost:3000',
                'hostHints' => ['localhost'],
                'entries' => ['script' => ['src/app.ts']],
            ],
        ];

        $this->Vite->script(['files' => ['src/app.ts'], 'block' => 'custom'], $config);

        $result = $this->View->fetch('custom');
        $this->assertStringContainsString('src/app.ts', $result);
    }

    /**
     * Test pluginScript method
     */
    public function testPluginScript(): void
    {
        $config = [
            'forceProductionMode' => true,
            'build' => [
                'manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json',
            ],
        ];

        $this->Vite->pluginScript('TestPlugin', false, ['files' => ['src/app.ts']], $config);

        $result = $this->View->fetch('script');
        $this->assertStringContainsString('assets/app-abc123.js', $result);
    }

    /**
     * Test pluginCss method
     */
    public function testPluginCss(): void
    {
        $config = [
            'forceProductionMode' => true,
            'build' => [
                'manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json',
            ],
        ];

        $this->Vite->pluginCss('TestPlugin', false, ['files' => ['src/style.css']], $config);

        $result = $this->View->fetch('css');
        $this->assertStringContainsString('assets/style-jkl012.css', $result);
    }

    /**
     * Test attributes are passed to Html helper
     */
    public function testAttributesArePassedToHtmlHelper(): void
    {
        $config = [
            'devServer' => [
                'url' => 'http://localhost:3000',
                'hostHints' => ['localhost'],
                'entries' => ['script' => ['src/app.ts']],
            ],
        ];

        $this->Vite->script([
            'files' => ['src/app.ts'],
            'attributes' => ['defer' => true],
        ], $config);

        $result = $this->View->fetch('script');
        $this->assertStringContainsString('defer', $result);
    }

    /**
     * Test css method with string shorthand
     */
    public function testCssAcceptsStringShorthand(): void
    {
        $config = [
            'devServer' => [
                'url' => 'http://localhost:3000',
                'hostHints' => ['localhost'],
                'entries' => ['style' => ['src/style.css']],
            ],
        ];

        $this->Vite->css('src/style.css', $config);

        $result = $this->View->fetch('css');
        $this->assertStringContainsString('http://localhost:3000/src/style.css', $result);
    }

    /**
     * Test that configuration is cached after first call
     *
     * Uses reflection to verify the cachedConfig property is populated.
     * This verifies optimization #1: configuration caching to reduce Configure::read calls.
     */
    public function testConfigurationIsCachedInProperty(): void
    {
        $reflection = new ReflectionClass($this->Vite);
        $property = $reflection->getProperty('cachedConfig');

        // Initially null
        $this->assertNull($property->getValue($this->Vite));

        // Call isDev() without config - triggers Configure::read and caching
        $this->Vite->isDev();

        // cachedConfig should now be populated
        $cachedConfig = $property->getValue($this->Vite);
        $this->assertInstanceOf(ViteConfig::class, $cachedConfig);

        // Second call should return same cached instance
        $this->Vite->isDev();
        /** @phpstan-ignore argument.unresolvableType */
        $this->assertSame($cachedConfig, $property->getValue($this->Vite));
    }

    /**
     * Test that custom config passed as argument is NOT cached
     *
     * Only default config from Configure should be cached.
     */
    public function testCustomConfigIsNotCached(): void
    {
        $reflection = new ReflectionClass($this->Vite);
        $property = $reflection->getProperty('cachedConfig');

        $customConfig = [
            'devServer' => ['hostHints' => ['localhost']],
        ];

        // Call with custom config - should NOT populate cache
        $this->Vite->isDev($customConfig);

        // Cache should still be null (custom configs aren't cached)
        $this->assertNull($property->getValue($this->Vite));
    }

    /**
     * Test that environment detection is cached after first call
     *
     * Uses reflection to verify the cachedEnvironment property is populated.
     * This verifies optimization #2: environment detection caching.
     */
    public function testEnvironmentDetectionIsCachedInProperty(): void
    {
        $reflection = new ReflectionClass($this->Vite);
        $property = $reflection->getProperty('cachedEnvironment');

        // Initially null
        $this->assertNull($property->getValue($this->Vite));

        // Call isDev() - triggers environment detection and caching
        $result = $this->Vite->isDev();

        // cachedEnvironment should now be populated
        $cachedEnv = $property->getValue($this->Vite);
        $this->assertInstanceOf(Environment::class, $cachedEnv);
        /** @phpstan-ignore instanceof.alwaysFalse */
        if ($cachedEnv instanceof Environment) {
            $this->assertSame($result, $cachedEnv->isDevelopment());
        }

        // Second call should return same cached instance
        $this->Vite->isDev();
        /** @phpstan-ignore argument.unresolvableType */
        $this->assertSame($cachedEnv, $property->getValue($this->Vite));
    }

    /**
     * Test that environment cache is only used with default config
     *
     * Custom configs should trigger fresh environment detection.
     */
    public function testEnvironmentCacheOnlyUsedWithDefaultConfig(): void
    {
        $reflection = new ReflectionClass($this->Vite);
        $property = $reflection->getProperty('cachedEnvironment');

        // First call with default config - caches environment
        $this->Vite->isDev();
        $cachedEnv = $property->getValue($this->Vite);
        $this->assertInstanceOf(Environment::class, $cachedEnv);

        // Call with custom config - should still use cache for subsequent default calls
        $customConfig = [
            'devServer' => ['hostHints' => ['localhost']],
        ];
        $this->Vite->isDev($customConfig);

        // Cache should be preserved
        $this->assertSame($cachedEnv, $property->getValue($this->Vite));
    }

    /**
     * Test that script() and css() methods work with cached configuration
     */
    public function testScriptAndCssUseCachedConfiguration(): void
    {
        $config = [
            'devServer' => [
                'url' => 'http://localhost:3000',
                'hostHints' => ['localhost'],
                'entries' => [
                    'script' => ['src/app.ts'],
                    'style' => ['src/style.css'],
                ],
            ],
        ];

        // Call script() which internally calls isDev() - both should benefit from caching
        $this->Vite->script([], $config);
        $this->Vite->css([], $config);

        // Verify both rendered correctly
        $scriptResult = $this->View->fetch('script');
        $cssResult = $this->View->fetch('css');

        $this->assertStringContainsString('http://localhost:3000', $scriptResult);
        $this->assertStringContainsString('http://localhost:3000', $cssResult);
    }

    /**
     * Test script() with preload option overrides config
     */
    public function testScriptWithPreloadOption(): void
    {
        $config = [
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest-with-imports.json'],
            'preload' => 'link-tag', // Default to preload
            'forceProductionMode' => true,
        ];

        // Override to disable preload
        $this->Vite->script(['files' => ['src/app.ts'], 'preload' => 'none'], $config);

        $result = $this->View->fetch('script');

        // Should not contain preload tags
        $this->assertStringNotContainsString('modulepreload', $result);
    }

    /**
     * Test script() preload defaults to config setting
     */
    public function testScriptPreloadDefaultsToConfigSetting(): void
    {
        $config = [
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest-with-imports.json'],
            'preload' => 'link-tag',
            'forceProductionMode' => true,
        ];

        $this->Vite->script(['files' => ['src/app.ts']], $config);

        $result = $this->View->fetch('script');

        // Should contain preload tags (default from config)
        $this->assertStringContainsString('modulepreload', $result);
    }

    /**
     * Test script() preload only in production mode
     */
    public function testScriptPreloadOnlyInProduction(): void
    {
        $config = [
            'devServer' => [
                'url' => 'http://localhost:3000',
                'hostHints' => ['localhost'],
                'entries' => ['script' => ['src/app.ts']],
            ],
            'preload' => 'link-tag',
        ];

        $this->Vite->script([], $config);

        $result = $this->View->fetch('script');

        // Development mode should never have preload tags
        $this->assertStringNotContainsString('modulepreload', $result);
    }

    /**
     * Test script with named config option
     */
    public function testScriptWithNamedConfig(): void
    {
        Configure::write('CakeVite', [
            'devServer' => [
                'url' => 'http://localhost:3000',
                'hostHints' => ['localhost'],
                'entries' => ['script' => ['src/main.ts']],
            ],
            'forceProductionMode' => true,
            'build' => [
                'manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json',
            ],
            'configs' => [
                'admin' => [
                    'devServer' => [
                        'url' => 'http://localhost:3001',
                    ],
                    'build' => [
                        'outDirectory' => 'admin',
                    ],
                ],
            ],
        ]);

        // Use named config via option
        $this->Vite->script(['config' => 'admin']);

        $result = $this->View->fetch('script');

        // Should use admin config (outDirectory affects asset URLs)
        $this->assertStringContainsString('/admin/', $result);
    }

    /**
     * Test CSS with named config option
     */
    public function testCssWithNamedConfig(): void
    {
        Configure::write('CakeVite', [
            'devServer' => [
                'url' => 'http://localhost:3000',
                'hostHints' => ['localhost'],
                'entries' => ['style' => ['src/style.css']],
            ],
            'forceProductionMode' => true,
            'build' => [
                'manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json',
            ],
            'configs' => [
                'marketing' => [
                    'build' => [
                        'outDirectory' => 'marketing',
                    ],
                ],
            ],
        ]);

        // Use named config via option
        $this->Vite->css(['config' => 'marketing']);

        $result = $this->View->fetch('css');

        // Should use marketing config
        $this->assertStringContainsString('/marketing/', $result);
    }

    /**
     * Test named config with direct config parameter
     */
    public function testScriptWithNamedConfigDirect(): void
    {
        Configure::write('CakeVite', [
            'devServer' => [
                'url' => 'http://localhost:3000',
                'hostHints' => ['localhost'],
                'entries' => ['script' => ['src/main.ts']],
            ],
            'forceProductionMode' => true,
            'build' => [
                'manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json',
            ],
            'configs' => [
                'api' => [
                    'build' => [
                        'outDirectory' => 'api',
                    ],
                ],
            ],
        ]);

        // Pass named config as ViteConfig parameter
        $config = ViteConfig::fromArray([], 'api');
        $this->Vite->script([], $config);

        $result = $this->View->fetch('script');

        // Should use api config
        $this->assertStringContainsString('/api/', $result);
    }

    /**
     * Test preload link tags render with additional attributes
     */
    public function testPreloadLinkTagsRenderWithAttributes(): void
    {
        $config = [
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest-with-imports.json'],
            'preload' => 'link-tag',
            'forceProductionMode' => true,
        ];

        // Mock AssetService to inject attributes
        $this->Vite->script(['files' => ['src/app.ts']], $config);

        $result = $this->View->fetch('script');

        // Should contain preload tags
        $this->assertStringContainsString('modulepreload', $result);
        $this->assertStringContainsString('<link rel="modulepreload"', $result);
    }

    /**
     * Test preload link tags handle crossorigin attribute
     */
    public function testPreloadLinkTagsWithCrossorigin(): void
    {
        Configure::write('CakeVite', [
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest-with-imports.json'],
            'preload' => 'link-tag',
            'forceProductionMode' => true,
        ]);

        $this->Vite->script(['files' => ['src/app.ts']]);

        $result = $this->View->fetch('script');

        // Verify preload tags are generated
        $this->assertStringContainsString('modulepreload', $result);
    }

    /**
     * Test buildPreloadLinkTag filters out rel attribute
     */
    public function testBuildPreloadLinkTagFiltersRelAttribute(): void
    {
        // Use reflection to test private method
        $reflection = new ReflectionClass($this->Vite);
        $method = $reflection->getMethod('buildPreloadLinkTag');

        $result = $method->invoke(
            $this->Vite,
            'modulepreload',
            '/assets/vendor.js',
            ['rel' => 'should-be-filtered', 'crossorigin' => 'anonymous'],
        );

        // Should only have modulepreload from parameter, not from attributes
        $this->assertStringContainsString('rel="modulepreload"', $result);
        $this->assertStringNotContainsString('should-be-filtered', $result);
        $this->assertStringContainsString('crossorigin="anonymous"', $result);
    }

    /**
     * Test buildPreloadLinkTag handles boolean attributes
     */
    public function testBuildPreloadLinkTagHandlesBooleanAttributes(): void
    {
        $reflection = new ReflectionClass($this->Vite);
        $method = $reflection->getMethod('buildPreloadLinkTag');

        $result = $method->invoke(
            $this->Vite,
            'modulepreload',
            '/assets/vendor.js',
            ['async' => true, 'defer' => false],
        );

        // async=true should render as boolean attribute
        $this->assertStringContainsString(' async', $result);
        $this->assertStringNotContainsString('async="', $result);

        // defer=false should not render
        $this->assertStringNotContainsString('defer', $result);
    }

    /**
     * Test buildPreloadLinkTag handles null attributes
     */
    public function testBuildPreloadLinkTagIgnoresNullAttributes(): void
    {
        $reflection = new ReflectionClass($this->Vite);
        $method = $reflection->getMethod('buildPreloadLinkTag');

        $result = $method->invoke(
            $this->Vite,
            'modulepreload',
            '/assets/vendor.js',
            ['crossorigin' => null, 'integrity' => 'sha256-abc123'],
        );

        // null attributes should be skipped
        $this->assertStringNotContainsString('crossorigin', $result);
        $this->assertStringContainsString('integrity="sha256-abc123"', $result);
    }

    /**
     * Test buildPreloadLinkTag escapes special characters
     */
    public function testBuildPreloadLinkTagEscapesSpecialCharacters(): void
    {
        $reflection = new ReflectionClass($this->Vite);
        $method = $reflection->getMethod('buildPreloadLinkTag');

        $result = $method->invoke(
            $this->Vite,
            'modulepreload',
            '/assets/vendor.js',
            ['data-test' => '<script>alert("xss")</script>'],
        );

        // Should escape HTML entities
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    /**
     * Test script method with block => false outputs inline
     */
    public function testScriptWithBlockFalseOutputsInline(): void
    {
        $config = [
            'devServer' => [
                'url' => 'http://localhost:3000',
                'hostHints' => ['localhost'],
                'entries' => ['script' => ['src/app.ts']],
            ],
        ];

        ob_start();
        $this->Vite->script(['files' => ['src/app.ts'], 'block' => false], $config);
        $output = ob_get_clean();

        // Should output directly instead of buffering to view block
        $this->assertStringContainsString('http://localhost:3000/@vite/client', $output);
        $this->assertStringContainsString('http://localhost:3000/src/app.ts', $output);
        $this->assertStringContainsString('type="module"', $output);

        // View block should be empty
        $this->assertEmpty($this->View->fetch('script'));
    }

    /**
     * Test script method with block => false in production mode
     */
    public function testScriptWithBlockFalseInProductionMode(): void
    {
        $config = [
            'forceProductionMode' => true,
            'build' => [
                'manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json',
            ],
        ];

        ob_start();
        $this->Vite->script(['files' => ['src/app.ts'], 'block' => false], $config);
        $output = ob_get_clean();

        // Should output directly
        $this->assertStringContainsString('assets/app-abc123.js', $output);
        $this->assertStringNotContainsString('localhost', $output);

        // View block should be empty
        $this->assertEmpty($this->View->fetch('script'));
    }

    /**
     * Test css method with block => false outputs inline
     */
    public function testCssWithBlockFalseOutputsInline(): void
    {
        $config = [
            'devServer' => [
                'url' => 'http://localhost:3000',
                'hostHints' => ['localhost'],
                'entries' => ['style' => ['src/style.css']],
            ],
        ];

        ob_start();
        $this->Vite->css(['files' => ['src/style.css'], 'block' => false], $config);
        $output = ob_get_clean();

        // Should output directly
        $this->assertStringContainsString('http://localhost:3000/src/style.css', $output);

        // View block should be empty
        $this->assertEmpty($this->View->fetch('css'));
    }

    /**
     * Test css method with block => false in production mode
     */
    public function testCssWithBlockFalseInProductionMode(): void
    {
        $config = [
            'forceProductionMode' => true,
            'build' => [
                'manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json',
            ],
        ];

        ob_start();
        $this->Vite->css(['files' => ['src/style.css'], 'block' => false], $config);
        $output = ob_get_clean();

        // Should output directly
        $this->assertStringContainsString('assets/style-jkl012.css', $output);

        // View block should be empty
        $this->assertEmpty($this->View->fetch('css'));
    }

    /**
     * Test script method with block => false outputs preload tags inline
     */
    public function testScriptWithBlockFalseOutputsPreloadTagsInline(): void
    {
        $config = [
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest-with-imports.json'],
            'preload' => 'link-tag',
            'forceProductionMode' => true,
        ];

        ob_start();
        $this->Vite->script(['files' => ['src/app.ts'], 'block' => false], $config);
        $output = ob_get_clean();

        // Preload tags should be output directly
        $this->assertStringContainsString('modulepreload', $output);
        $this->assertStringContainsString('<link rel="modulepreload"', $output);

        // View block should be empty
        $this->assertEmpty($this->View->fetch('script'));
    }

    /**
     * Test script method with block => false outputs dependent CSS inline
     */
    public function testScriptWithBlockFalseOutputsDependentCssInline(): void
    {
        $config = [
            'forceProductionMode' => true,
            'build' => [
                'manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json',
            ],
        ];

        ob_start();
        $this->Vite->script(['files' => ['src/app.ts'], 'block' => false], $config);
        $output = ob_get_clean();

        // Dependent CSS should be output directly
        $this->assertStringContainsString('assets/app-xyz789.css', $output);

        // CSS view block should be empty
        $this->assertEmpty($this->View->fetch('css'));
    }
}
