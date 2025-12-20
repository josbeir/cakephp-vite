<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase\ValueObject;

use CakeVite\Enum\AssetType;
use CakeVite\Enum\ScriptType;
use CakeVite\ValueObject\ManifestEntry;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

/**
 * ManifestEntry Value Object Test
 *
 * Following TDD principles - this test is written BEFORE the value object exists.
 */
class ManifestEntryTest extends TestCase
{
    /**
     * Test creating from manifest data
     */
    public function testFromManifestData(): void
    {
        $data = new stdClass();
        $data->file = 'assets/app-abc123.js';
        $data->src = 'src/app.ts';
        $data->isEntry = true;
        $data->css = ['assets/app-xyz789.css'];
        $data->imports = ['_vendor-def456.js'];

        $entry = ManifestEntry::fromManifestData('src/app.ts', $data, 'dist');

        $this->assertSame('src/app.ts', $entry->key);
        $this->assertSame('assets/app-abc123.js', $entry->file);
        $this->assertSame('src/app.ts', $entry->src);
        $this->assertTrue($entry->isEntry);
        $this->assertSame(['assets/app-xyz789.css'], $entry->css);
        $this->assertSame(['_vendor-def456.js'], $entry->imports);
        $this->assertSame('dist', $entry->buildDirectory);
    }

    /**
     * Test creating with minimal data (defaults)
     */
    public function testFromManifestDataWithDefaults(): void
    {
        $data = new stdClass();
        $data->file = 'assets/style.css';

        $entry = ManifestEntry::fromManifestData('src/style.css', $data, null);

        $this->assertSame('src/style.css', $entry->key);
        $this->assertSame('assets/style.css', $entry->file);
        $this->assertSame('src/style.css', $entry->src);
        $this->assertFalse($entry->isEntry);
        $this->assertSame([], $entry->css);
        $this->assertSame([], $entry->imports);
        $this->assertNull($entry->buildDirectory);
    }

    /**
     * Test getAssetType returns Script for JS files
     */
    public function testGetAssetTypeReturnsScriptForJsFiles(): void
    {
        $data = new stdClass();
        $data->file = 'app.js';

        $entry = ManifestEntry::fromManifestData('app.js', $data, null);

        $this->assertSame(AssetType::Script, $entry->getAssetType());
    }

    /**
     * Test getAssetType returns Style for CSS files
     */
    public function testGetAssetTypeReturnsStyleForCssFiles(): void
    {
        $data = new stdClass();
        $data->file = 'style.css';

        $entry = ManifestEntry::fromManifestData('style.css', $data, null);

        $this->assertSame(AssetType::Style, $entry->getAssetType());
    }

    /**
     * Test getScriptType returns Module for regular scripts
     */
    public function testGetScriptTypeReturnsModuleForRegularScripts(): void
    {
        $data = new stdClass();
        $data->file = 'assets/app-abc123.js';

        $entry = ManifestEntry::fromManifestData('src/app.ts', $data, null);

        $this->assertSame(ScriptType::Module, $entry->getScriptType());
    }

    /**
     * Test getScriptType returns Legacy for legacy scripts
     */
    public function testGetScriptTypeReturnsLegacyForLegacyScripts(): void
    {
        $data = new stdClass();
        $data->file = 'assets/app-legacy-abc123.js';

        $entry = ManifestEntry::fromManifestData('src/app-legacy.ts', $data, null);

        $this->assertSame(ScriptType::Legacy, $entry->getScriptType());
    }

    /**
     * Test getScriptType returns Polyfill for polyfill scripts
     */
    public function testGetScriptTypeReturnsPolyfillForPolyfillScripts(): void
    {
        $data = new stdClass();
        $data->file = 'assets/polyfills-abc123.js';

        $entry = ManifestEntry::fromManifestData('src/polyfills.ts', $data, null);

        $this->assertSame(ScriptType::Polyfill, $entry->getScriptType());
    }

    /**
     * Test getScriptType returns null for CSS files
     */
    public function testGetScriptTypeReturnsNullForCssFiles(): void
    {
        $data = new stdClass();
        $data->file = 'style.css';

        $entry = ManifestEntry::fromManifestData('style.css', $data, null);

        $this->assertNull($entry->getScriptType());
    }

    /**
     * Test getUrl without build directory
     */
    public function testGetUrlWithoutBuildDirectory(): void
    {
        $data = new stdClass();
        $data->file = 'assets/app.js';

        $entry = ManifestEntry::fromManifestData('src/app.ts', $data, null);

        $this->assertSame('/assets/app.js', $entry->getUrl());
    }

    /**
     * Test getUrl with build directory
     */
    public function testGetUrlWithBuildDirectory(): void
    {
        $data = new stdClass();
        $data->file = 'assets/app.js';

        $entry = ManifestEntry::fromManifestData('src/app.ts', $data, 'dist');

        $this->assertSame('/dist/assets/app.js', $entry->getUrl());
    }

    /**
     * Test getDependentCssUrls returns CSS with build directory
     */
    public function testGetDependentCssUrlsWithBuildDirectory(): void
    {
        $data = new stdClass();
        $data->file = 'assets/app.js';
        $data->css = ['assets/app.css', 'assets/vendor.css'];

        $entry = ManifestEntry::fromManifestData('src/app.ts', $data, 'build');

        $expected = ['/build/assets/app.css', '/build/assets/vendor.css'];
        $this->assertSame($expected, $entry->getDependentCssUrls());
    }

    /**
     * Test getDependentCssUrls returns empty array when no CSS
     */
    public function testGetDependentCssUrlsReturnsEmptyArrayWhenNoCss(): void
    {
        $data = new stdClass();
        $data->file = 'assets/app.js';

        $entry = ManifestEntry::fromManifestData('src/app.ts', $data, null);

        $this->assertSame([], $entry->getDependentCssUrls());
    }

    /**
     * Test matches method with src property
     */
    public function testMatchesWithSrcProperty(): void
    {
        $data = new stdClass();
        $data->file = 'assets/app.js';
        $data->src = 'src/pages/home.ts';

        $entry = ManifestEntry::fromManifestData('src/pages/home.ts', $data, null);

        $this->assertTrue($entry->matches('pages', 'src'));
        $this->assertTrue($entry->matches('home.ts', 'src'));
        $this->assertFalse($entry->matches('admin', 'src'));
    }

    /**
     * Test matches method with file property
     */
    public function testMatchesWithFileProperty(): void
    {
        $data = new stdClass();
        $data->file = 'assets/home-abc123.js';
        $data->src = 'src/pages/home.ts';

        $entry = ManifestEntry::fromManifestData('src/pages/home.ts', $data, null);

        $this->assertTrue($entry->matches('home', 'file'));
        $this->assertTrue($entry->matches('abc123', 'file'));
        $this->assertFalse($entry->matches('admin', 'file'));
    }

    /**
     * Test matches method with key property
     */
    public function testMatchesWithKeyProperty(): void
    {
        $data = new stdClass();
        $data->file = 'assets/app.js';

        $entry = ManifestEntry::fromManifestData('src/pages/home.ts', $data, null);

        $this->assertTrue($entry->matches('pages', 'key'));
        $this->assertTrue($entry->matches('home', 'key'));
        $this->assertFalse($entry->matches('admin', 'key'));
    }

    /**
     * Test ManifestEntry is readonly (immutable)
     */
    public function testManifestEntryIsReadonly(): void
    {
        $data = new stdClass();
        $data->file = 'test.js';

        $entry = ManifestEntry::fromManifestData('test.js', $data, null);

        $reflection = new ReflectionClass($entry);
        $this->assertTrue($reflection->isReadOnly());
    }

    /**
     * Test getImportUrls returns import URLs
     */
    public function testGetImportUrlsReturnsImportUrls(): void
    {
        $data = new stdClass();
        $data->file = 'assets/app.js';
        $data->imports = ['assets/vendor.js', 'assets/utils.js'];

        $entry = ManifestEntry::fromManifestData('src/app.ts', $data, null);

        $expected = ['/assets/vendor.js', '/assets/utils.js'];
        $this->assertSame($expected, $entry->getImportUrls());
    }

    /**
     * Test getImportUrls with no imports returns empty array
     */
    public function testGetImportUrlsWithNoImportsReturnsEmptyArray(): void
    {
        $data = new stdClass();
        $data->file = 'assets/app.js';

        $entry = ManifestEntry::fromManifestData('src/app.ts', $data, null);

        $this->assertSame([], $entry->getImportUrls());
    }

    /**
     * Test getImportUrls includes build directory
     */
    public function testGetImportUrlsIncludesBuildDirectory(): void
    {
        $data = new stdClass();
        $data->file = 'assets/app.js';
        $data->imports = ['assets/vendor.js'];

        $entry = ManifestEntry::fromManifestData('src/app.ts', $data, 'build');

        $expected = ['/build/assets/vendor.js'];
        $this->assertSame($expected, $entry->getImportUrls());
    }
}
