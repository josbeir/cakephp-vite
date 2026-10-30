<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase\Service;

use Cake\Http\ServerRequest;
use CakeVite\Exception\ConfigurationException;
use CakeVite\Exception\ManifestException;
use CakeVite\Service\AssetService;
use CakeVite\Service\EnvironmentService;
use CakeVite\Service\ManifestService;
use CakeVite\ValueObject\AssetTag;
use CakeVite\ValueObject\ViteConfig;
use PHPUnit\Framework\TestCase;

/** Exercises exact asset resolution without adding script or stylesheet tags. */
class AssetUrlServiceTest extends TestCase
{
    /** Clear request-local manifest state between independent tests. */
    protected function tearDown(): void
    {
        ManifestService::clearCache();
        parent::tearDown();
    }

    /** Development URLs use the configured server without reading a manifest. */
    public function testDevelopmentUrl(): void
    {
        $service = $this->service('localhost');
        $config = ViteConfig::fromArray([
            'devServer' => ['url' => 'https://localhost:5173/base/'],
            'build' => ['manifestPath' => TESTS . 'Fixture/missing.json'],
        ]);
        $this->assertSame('https://localhost:5173/base/src/icons.svg', $service->resolveAssetUrl('/src/icons.svg', $config));
    }

    /** All manifest resources resolve, including non-entry assets and build-directory prefixes. */
    public function testProductionUrls(): void
    {
        $service = $this->service('example.com');
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture/manifest-assets.json', 'outDirectory' => 'build'],
        ]);
        $this->assertSame('/build/assets/icons-fedcba.svg', $service->resolveAssetUrl('src/icons.svg', $config));
        $this->assertSame('/build/assets/font-456def.woff2', $service->resolveAssetUrl('src/font.woff2', $config));
        $this->assertSame('/build/assets/logo-123abc.png', $service->resolveAssetUrl('/src/logo.png', $config));
    }

    /** Static files marked as entries must not be emitted as script or stylesheet tags. */
    public function testStaticEntriesAreNotScripts(): void
    {
        $service = $this->service('example.com');
        $config = ViteConfig::fromArray(['build' => ['manifestPath' => TESTS . 'Fixture/manifest-assets.json']]);
        $scripts = $service->generateScriptTags($config);
        $styles = $service->generateStyleTags($config);
        $this->assertSame(['/assets/app-abc123.js'], array_map(static fn(AssetTag $tag): string => $tag->url, $scripts));
        $this->assertSame(['/assets/style-def456.css'], array_map(static fn(AssetTag $tag): string => $tag->url, $styles));
    }

    /** Asset paths are exact manifest keys, never wildcard filters or unhashed fallbacks. */
    public function testMissingEntry(): void
    {
        $config = ViteConfig::fromArray(['build' => ['manifestPath' => TESTS . 'Fixture/manifest-assets.json']]);
        $this->expectException(ManifestException::class);
        $this->expectExceptionMessage('src/*.svg');
        $this->service('example.com')->resolveAssetUrl('src/*.svg', $config);
    }

    /** An empty path must not turn into a development-server root URL. */
    public function testEmptyPath(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->service('localhost')->resolveAssetUrl('/', ViteConfig::fromArray([]));
    }

    /** Keep test requests explicit so environment selection follows the public configuration. */
    private function service(string $host): AssetService
    {
        return new AssetService(
            new EnvironmentService(new ServerRequest(['environment' => ['HTTP_HOST' => $host]])),
            new ManifestService(),
        );
    }
}
