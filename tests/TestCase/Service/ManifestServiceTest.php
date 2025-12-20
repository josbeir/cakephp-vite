<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase\Service;

use CakeVite\Exception\ManifestException;
use CakeVite\Service\ManifestService;
use CakeVite\ValueObject\ManifestCollection;
use CakeVite\ValueObject\ViteConfig;
use PHPUnit\Framework\TestCase;

/**
 * ManifestService Test
 *
 * Following TDD principles - this test is written BEFORE the service exists.
 */
class ManifestServiceTest extends TestCase
{
    /**
     * Tear down test to clear manifest cache
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        ManifestService::clearCache();
    }

    /**
     * Test load valid manifest file
     */
    public function testLoadValidManifest(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json'],
        ]);

        $service = new ManifestService();
        $collection = $service->load($config);

        $this->assertInstanceOf(ManifestCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    /**
     * Test load parses all manifest entries
     */
    public function testLoadParsesAllEntries(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json'],
        ]);

        $service = new ManifestService();
        $collection = $service->load($config);

        $this->assertCount(4, $collection);
    }

    /**
     * Test load creates ManifestEntry objects with correct data
     */
    public function testLoadCreatesCorrectManifestEntries(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json'],
        ]);

        $service = new ManifestService();
        $collection = $service->load($config);

        $entries = $collection->toArray();
        $this->assertSame('src/app.ts', $entries[0]->key);
        $this->assertSame('assets/app-abc123.js', $entries[0]->file);
        $this->assertTrue($entries[0]->isEntry);
    }

    /**
     * Test load applies build directory to entries
     */
    public function testLoadAppliesBuildDirectory(): void
    {
        $config = ViteConfig::fromArray([
            'build' => [
                'manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json',
                'outDirectory' => 'dist',
            ],
        ]);

        $service = new ManifestService();
        $collection = $service->load($config);

        $entry = $collection->toArray()[0];
        $this->assertSame('dist', $entry->buildDirectory);
        $this->assertSame('/dist/assets/app-abc123.js', $entry->getUrl());
    }

    /**
     * Test load throws exception when manifest file not found
     */
    public function testLoadThrowsExceptionWhenManifestNotFound(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => '/does/not/exist.json'],
        ]);

        $service = new ManifestService();

        $this->expectException(ManifestException::class);
        $this->expectExceptionMessageMatches('/not found or not readable/');
        $service->load($config);
    }

    /**
     * Test load throws exception with helpful message about building
     */
    public function testLoadExceptionMessageMentionsBuilding(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => '/missing/manifest.json'],
        ]);

        $service = new ManifestService();

        $this->expectException(ManifestException::class);
        $this->expectExceptionMessageMatches('/npm run build|vite build/');
        $service->load($config);
    }

    /**
     * Test load throws exception for invalid JSON
     */
    public function testLoadThrowsExceptionForInvalidJson(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest-invalid.json'],
        ]);

        $service = new ManifestService();

        $this->expectException(ManifestException::class);
        $this->expectExceptionMessageMatches('/Invalid JSON/');
        $service->load($config);
    }

    /**
     * Test load handles manifest without build directory
     */
    public function testLoadHandlesManifestWithoutBuildDirectory(): void
    {
        $config = ViteConfig::fromArray([
            'build' => [
                'manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json',
                'outDirectory' => false,
            ],
        ]);

        $service = new ManifestService();
        $collection = $service->load($config);

        $entry = $collection->toArray()[0];
        $this->assertNull($entry->buildDirectory);
        $this->assertSame('/assets/app-abc123.js', $entry->getUrl());
    }

    /**
     * Test load handles entries without src property
     */
    public function testLoadHandlesEntriesWithoutSrc(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json'],
        ]);

        $service = new ManifestService();
        $collection = $service->load($config);

        $vendorEntry = $collection->toArray()[3];
        $this->assertSame('_vendor-def456.js', $vendorEntry->key);
        $this->assertSame('_vendor-def456.js', $vendorEntry->src);
    }

    /**
     * Test load handles entries with minimal data
     */
    public function testLoadHandlesEntriesWithMinimalData(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json'],
        ]);

        $service = new ManifestService();
        $collection = $service->load($config);

        $cssEntry = $collection->toArray()[2];
        $this->assertSame('src/style.css', $cssEntry->key);
        $this->assertSame([], $cssEntry->css);
        $this->assertSame([], $cssEntry->imports);
    }

    /**
     * Test manifest is cached within request lifecycle
     */
    public function testManifestIsCachedDuringRequest(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json'],
        ]);

        $service = new ManifestService();

        // First load - reads from disk
        $manifest1 = $service->load($config);

        // Second load - should return cached instance
        $manifest2 = $service->load($config);

        // Should be the exact same object (not just equal, but identical)
        $this->assertSame($manifest1, $manifest2);
    }

    /**
     * Test clearCache resets the manifest cache
     */
    public function testClearCacheResetsManifestCache(): void
    {
        $config = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json'],
        ]);

        $service = new ManifestService();

        $manifest1 = $service->load($config);

        ManifestService::clearCache();

        $manifest2 = $service->load($config);

        // After clearing cache, should be different instances
        $this->assertNotSame($manifest1, $manifest2);
        // But content should be equal
        $this->assertEquals($manifest1, $manifest2);
    }

    /**
     * Test different manifest paths are cached separately
     */
    public function testDifferentManifestsAreCachedSeparately(): void
    {
        $config1 = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest.json'],
        ]);

        $config2 = ViteConfig::fromArray([
            'build' => ['manifestPath' => TESTS . 'Fixture' . DS . 'manifest-legacy.json'],
        ]);

        $service = new ManifestService();

        $manifest1 = $service->load($config1);
        $manifest2 = $service->load($config2);

        // Different manifests should be different objects
        $this->assertNotSame($manifest1, $manifest2);
    }
}
