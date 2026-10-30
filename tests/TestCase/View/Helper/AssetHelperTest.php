<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase\View\Helper;

use Cake\Cache\Cache;
use Cake\Cache\Engine\ArrayEngine;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use CakeVite\Service\ManifestService;
use CakeVite\ValueObject\ViteConfig;
use CakeVite\View\Helper\ViteHelper;

/** Verifies raw asset URLs through the same configuration and routing as the existing helper. */
class AssetHelperTest extends TestCase
{
    /** Release custom cache and router state after each test. */
    protected function tearDown(): void
    {
        ManifestService::clearCache();
        Cache::drop('asset_url_test');
        Plugin::getCollection()->remove('TestPlugin');
        Router::reload();
        parent::tearDown();
    }

    /** Resolving a development asset preserves the script block and later HMR client inclusion. */
    public function testDevelopmentAssetDoesNotRenderTags(): void
    {
        $view = new View(new ServerRequest(['environment' => ['HTTP_HOST' => 'localhost']]));
        $helper = new ViteHelper($view);
        $config = ['devServer' => ['url' => 'http://localhost:5173']];
        $this->assertSame('http://localhost:5173/src/logo.png', $helper->asset('src/logo.png', $config));
        $this->assertSame('', $view->fetch('script'));
        $this->assertSame('', $view->fetch('css'));
        $helper->script(['files' => ['src/app.ts']], $config);
        $this->assertStringContainsString('http://localhost:5173/@vite/client', $view->fetch('script'));
    }

    /** Production asset calls return URLs, support SVG fragments, and leave view blocks untouched. */
    public function testProductionAsset(): void
    {
        $view = new View(new ServerRequest());
        $helper = new ViteHelper($view);
        $config = ViteConfig::fromArray([
            'forceProductionMode' => true,
            'build' => ['manifestPath' => TESTS . 'Fixture/manifest-assets.json', 'outDirectory' => 'build'],
        ]);
        $this->assertSame('/build/assets/icons-fedcba.svg#image-up', $helper->asset('src/icons.svg', $config) . '#image-up');
        $this->assertSame('/build/assets/logo-123abc.png', $helper->asset('src/logo.png', $config));
        $this->assertSame('', $view->fetch('script'));
        $this->assertSame('', $view->fetch('css'));
    }

    /** Named configurations sharing a manifest retain their own output prefixes in both cache layers. */
    public function testNamedConfigurationsAndCaching(): void
    {
        Cache::setConfig('asset_url_test', ['className' => ArrayEngine::class]);
        Configure::write('CakeVite', [
            'forceProductionMode' => true,
            'build' => ['manifestPath' => TESTS . 'Fixture/manifest-assets.json'],
            'cache' => ['config' => 'asset_url_test'],
            'configs' => ['admin' => ['build' => ['outDirectory' => 'admin']]],
        ]);
        $helper = new ViteHelper(new View(new ServerRequest()));
        $this->assertSame('/assets/icons-fedcba.svg', $helper->asset('src/icons.svg'));
        $this->assertSame('/admin/assets/icons-fedcba.svg', $helper->asset('src/icons.svg', ['config' => 'admin']));
        ManifestService::clearCache();
        $this->assertSame('/assets/icons-fedcba.svg', $helper->asset('src/icons.svg'));
        $this->assertSame('/admin/assets/icons-fedcba.svg', $helper->asset('src/icons.svg', ['config' => 'admin']));
    }

    /** Plugin assets resolve to public URLs through CakePHP instead of leaking plugin aliases. */
    public function testPluginAsset(): void
    {
        Plugin::getCollection()->add(new BasePlugin(['name' => 'TestPlugin', 'path' => TESTS . 'test_app/']));
        $helper = new ViteHelper(new View(new ServerRequest()));
        $this->assertSame('/test_plugin/dist/assets/logo-123abc.png', $helper->asset('src/logo.png', [
            'forceProductionMode' => true,
            'plugin' => 'TestPlugin',
            'build' => ['manifestPath' => TESTS . 'Fixture/manifest-assets.json', 'outDirectory' => 'dist'],
        ]));
    }

    /** CakePHP application base paths apply to asset URLs as they do to script and stylesheet tags. */
    public function testApplicationBasePath(): void
    {
        Router::setRequest(new ServerRequest(['webroot' => '/subdirectory/']));
        $helper = new ViteHelper(new View(Router::getRequest()));
        $this->assertSame('/subdirectory/build/assets/logo-123abc.png', $helper->asset('src/logo.png', [
            'forceProductionMode' => true,
            'build' => ['manifestPath' => TESTS . 'Fixture/manifest-assets.json', 'outDirectory' => 'build'],
        ]));
    }

    /** Already-hashed manifest paths stay unchanged even when CakePHP asset timestamps are forced globally. */
    public function testNoAdditionalAssetTimestamp(): void
    {
        $directory = TMP . 'asset-url-webroot';
        mkdir($directory . '/assets', 0o770, true);
        file_put_contents($directory . '/assets/logo-123abc.png', 'fixture');
        Configure::write('App.wwwRoot', $directory . DS);
        Configure::write('Asset.timestamp', 'force');
        try {
            $helper = new ViteHelper(new View(new ServerRequest()));
            $this->assertSame('/assets/logo-123abc.png', $helper->asset('src/logo.png', [
                'forceProductionMode' => true,
                'build' => ['manifestPath' => TESTS . 'Fixture/manifest-assets.json'],
            ]));
        } finally {
            unlink($directory . '/assets/logo-123abc.png');
            rmdir($directory . '/assets');
            rmdir($directory);
        }
    }
}
