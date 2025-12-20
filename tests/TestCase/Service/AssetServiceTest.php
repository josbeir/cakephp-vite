<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase\Service;

use Cake\Http\ServerRequest;
use CakeVite\Enum\AssetType;
use CakeVite\Exception\ConfigurationException;
use CakeVite\Service\AssetService;
use CakeVite\Service\EnvironmentService;
use CakeVite\Service\ManifestService;
use CakeVite\ValueObject\ViteConfig;
use PHPUnit\Framework\TestCase;

/**
 * AssetService Test
 *
 * Following TDD principles - this test is written BEFORE the service exists.
 */
class AssetServiceTest extends TestCase
{
    private ManifestService $manifestService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manifestService = new ManifestService();
    }

    /**
     * Tear down test to clear manifest cache
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        ManifestService::clearCache();
    }

    /**
     * Create environment service for development
     */
    private function createDevEnvironmentService(): EnvironmentService
    {
        $request = new ServerRequest([
            'environment' => ['HTTP_HOST' => 'localhost'],
        ]);

        return new EnvironmentService($request);
    }

    /**
     * Create environment service for production
     */
    private function createProdEnvironmentService(): EnvironmentService
    {
        $request = new ServerRequest([
            'environment' => ['HTTP_HOST' => 'example.com'],
        ]);

        return new EnvironmentService($request);
    }

    /**
     * Test generateScriptTags in development mode includes Vite client
     */
    public function testGenerateScriptTagsInDevelopmentIncludesViteClient(): void
    {
        $config = ViteConfig::fromArray([
            'devServer' => [
                'url' => 'http://localhost:3000',
                'entries' => ['script' => ['src/app.ts']],
            ],
        ]);

        $service = new AssetService($this->createDevEnvironmentService(), $this->manifestService);
        $tags = $service->generateScriptTags($config);

        $this->assertGreaterThan(0, count($tags));
        $this->assertSame('http://localhost:3000/@vite/client', $tags[0]->url);
        $this->assertSame(AssetType::Script, $tags[0]->type);
        $this->assertSame('module', $tags[0]->attributes['type']);
    }

    /**
     * Test generateScriptTags in development mode includes entry files
     */
    public function testGenerateScriptTagsInDevelopmentIncludesEntries(): void
    {
        $config = ViteConfig::fromArray([
            'devServer' => [
                'url' => 'http://localhost:3000',
                'entries' => ['script' => ['src/app.ts', 'src/admin.ts']],
            ],
        ]);

        $service = new AssetService($this->createDevEnvironmentService(), $this->manifestService);
        $tags = $service->generateScriptTags($config);

        $this->assertCount(3, $tags); // Vite client + 2 entries
        $this->assertSame('http://localhost:3000/src/app.ts', $tags[1]->url);
        $this->assertSame('http://localhost:3000/src/admin.ts', $tags[2]->url);
    }

    /**
     * Test generateScriptTags in development throws exception without entries
     */
    public function testGenerateScriptTagsInDevelopmentThrowsWithoutEntries(): void
    {
        $config = ViteConfig::fromArray([
            'devServer' => ['url' => 'http://localhost:3000'],
        ]);

        $service = new AssetService($this->createDevEnvironmentService(), $this->manifestService);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches('/No script entries configured/');
        $service->generateScriptTags($config);
    }

    /**
     * Test generateScriptTags in development accepts files option
     */
    public function testGenerateScriptTagsInDevelopmentAcceptsFilesOption(): void
    {
        $config = ViteConfig::fromArray([
            'devServer' => ['url' => 'http://localhost:3000'],
        ]);

        $service = new AssetService($this->createDevEnvironmentService(), $this->manifestService);
        $tags = $service->generateScriptTags($config, ['files' => ['custom.ts']]);

        $this->assertCount(2, $tags); // Vite client + custom entry
        $this->assertSame('http://localhost:3000/custom.ts', $tags[1]->url);
    }

    /**
     * Test generateScriptTags in production mode loads from manifest
     */
    public function testGenerateScriptTagsInProductionLoadsFromManifest(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json'],
        ]);

        $service = new AssetService($this->createProdEnvironmentService(), $this->manifestService);
        $tags = $service->generateScriptTags($config);

        $this->assertGreaterThan(0, count($tags));
        $this->assertSame('/assets/app-abc123.js', $tags[0]->url);
        $this->assertSame('module', $tags[0]->attributes['type']);
    }

    /**
     * Test generateScriptTags in production filters by files option
     */
    public function testGenerateScriptTagsInProductionFiltersFiles(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json'],
        ]);

        $service = new AssetService($this->createProdEnvironmentService(), $this->manifestService);
        $tags = $service->generateScriptTags($config, ['files' => ['admin']]);

        $this->assertCount(1, $tags);
        $this->assertStringContainsString('admin', $tags[0]->url);
    }

    /**
     * Test generateScriptTags sorts by load order (polyfills first)
     */
    public function testGenerateScriptTagsSortsByLoadOrder(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest-with-legacy.json'],
        ]);

        $service = new AssetService($this->createProdEnvironmentService(), $this->manifestService);
        $tags = $service->generateScriptTags($config);

        // Should have at least 3 script entries (polyfills, legacy, modules)
        $this->assertGreaterThanOrEqual(3, count($tags));

        // Find position of each type
        $polyfillPos = null;
        $legacyPos = null;
        $modulePos = null;

        foreach ($tags as $index => $tag) {
            if (str_contains($tag->url, 'polyfills')) {
                $polyfillPos = $index;
            } elseif (str_contains($tag->url, 'legacy')) {
                $legacyPos = $index;
            } elseif (str_contains($tag->url, 'app-abc')) {
                // Match app.ts but not app-legacy.ts
                $modulePos = $index;
            }
        }

        // Verify polyfills come before legacy and modules
        $this->assertNotNull($polyfillPos, 'Polyfills should be present');
        $this->assertNotNull($legacyPos, 'Legacy should be present');
        $this->assertNotNull($modulePos, 'Module should be present');
        $this->assertLessThan($legacyPos, $polyfillPos, 'Polyfills should come before legacy');
        $this->assertLessThan($modulePos, $polyfillPos, 'Polyfills should come before modules');
        $this->assertLessThan($modulePos, $legacyPos, 'Legacy should come before modules');
    }

    /**
     * Test generateScriptTags adds nomodule attribute to legacy scripts
     */
    public function testGenerateScriptTagsAddsNomoduleToLegacy(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest-with-legacy.json'],
        ]);

        $service = new AssetService($this->createProdEnvironmentService(), $this->manifestService);
        $tags = $service->generateScriptTags($config);

        // Find legacy tag
        $legacyTag = null;
        foreach ($tags as $tag) {
            if (str_contains($tag->url, 'legacy')) {
                $legacyTag = $tag;
                break;
            }
        }

        $this->assertNotNull($legacyTag);
        $this->assertTrue($legacyTag->attributes['nomodule']);
    }

    /**
     * Test generateScriptTags applies custom attributes
     */
    public function testGenerateScriptTagsAppliesCustomAttributes(): void
    {
        $config = ViteConfig::fromArray([
            'devServer' => [
                'url' => 'http://localhost:3000',
                'entries' => ['script' => ['app.ts']],
            ],
        ]);

        $service = new AssetService($this->createDevEnvironmentService(), $this->manifestService);
        $tags = $service->generateScriptTags($config, ['attributes' => ['defer' => true]]);

        $this->assertTrue($tags[1]->attributes['defer']);
    }

    /**
     * Test generateStyleTags in development mode
     */
    public function testGenerateStyleTagsInDevelopment(): void
    {
        $config = ViteConfig::fromArray([
            'devServer' => [
                'url' => 'http://localhost:3000',
                'entries' => ['style' => ['src/style.css']],
            ],
        ]);

        $service = new AssetService($this->createDevEnvironmentService(), $this->manifestService);
        $tags = $service->generateStyleTags($config);

        $this->assertCount(1, $tags);
        $this->assertSame('http://localhost:3000/src/style.css', $tags[0]->url);
        $this->assertSame(AssetType::Style, $tags[0]->type);
    }

    /**
     * Test generateStyleTags in production mode
     */
    public function testGenerateStyleTagsInProduction(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json'],
        ]);

        $service = new AssetService($this->createProdEnvironmentService(), $this->manifestService);
        $tags = $service->generateStyleTags($config);

        $this->assertGreaterThan(0, count($tags));
        $this->assertSame(AssetType::Style, $tags[0]->type);
        $this->assertStringContainsString('.css', $tags[0]->url);
    }

    /**
     * Test generateStyleTags filters by files option
     */
    public function testGenerateStyleTagsFiltersFiles(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json'],
        ]);

        $service = new AssetService($this->createProdEnvironmentService(), $this->manifestService);
        $tags = $service->generateStyleTags($config, ['files' => ['style']]);

        $this->assertCount(1, $tags);
        $this->assertStringContainsString('style', $tags[0]->url);
    }

    /**
     * Test generateStyleTags applies custom attributes
     */
    public function testGenerateStyleTagsAppliesCustomAttributes(): void
    {
        $config = ViteConfig::fromArray([
            'devServer' => [
                'url' => 'http://localhost:3000',
                'entries' => ['style' => ['style.css']],
            ],
        ]);

        $service = new AssetService($this->createDevEnvironmentService(), $this->manifestService);
        $tags = $service->generateStyleTags($config, ['attributes' => ['media' => 'print']]);

        $this->assertSame('print', $tags[0]->attributes['media']);
    }

    /**
     * Test generateScriptTags with build directory
     */
    public function testGenerateScriptTagsWithBuildDirectory(): void
    {
        $config = ViteConfig::fromArray([
            'build' => [
                'manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json',
                'outDirectory' => 'dist',
            ],
        ]);

        $service = new AssetService($this->createProdEnvironmentService(), $this->manifestService);
        $tags = $service->generateScriptTags($config);

        $this->assertStringStartsWith('/dist/', $tags[0]->url);
    }
}
