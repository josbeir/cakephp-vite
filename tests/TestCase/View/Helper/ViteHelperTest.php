<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase\View\Helper;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use CakeVite\View\Helper\ViteHelper;

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
}
