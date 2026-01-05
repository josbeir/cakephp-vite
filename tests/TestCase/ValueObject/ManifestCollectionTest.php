<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase\ValueObject;

use Cake\TestSuite\TestCase;
use CakeVite\Enum\AssetType;
use CakeVite\ValueObject\ManifestCollection;
use CakeVite\ValueObject\ManifestEntry;
use stdClass;

/**
 * ManifestCollection Test Case
 */
class ManifestCollectionTest extends TestCase
{
    /**
     * Test findByKey returns entry when key exists
     */
    public function testFindByKeyReturnsEntryWhenExists(): void
    {
        $data1 = new stdClass();
        $data1->file = 'assets/app.js';

        $entry1 = ManifestEntry::fromManifestData('src/app.ts', $data1, null);

        $data2 = new stdClass();
        $data2->file = 'assets/vendor.js';

        $entry2 = ManifestEntry::fromManifestData('_vendor.js', $data2, null);

        $collection = new ManifestCollection([$entry1, $entry2]);

        $found = $collection->findByKey('_vendor.js');

        $this->assertNotNull($found);
        $this->assertSame('_vendor.js', $found->key);
        $this->assertSame('assets/vendor.js', $found->file);
    }

    /**
     * Test findByKey returns null when key does not exist
     */
    public function testFindByKeyReturnsNullWhenNotExists(): void
    {
        $data = new stdClass();
        $data->file = 'assets/app.js';

        $entry = ManifestEntry::fromManifestData('src/app.ts', $data, null);

        $collection = new ManifestCollection([$entry]);

        $found = $collection->findByKey('nonexistent.js');

        $this->assertNull($found);
    }

    /**
     * Test resolveImportUrls resolves manifest keys to file URLs
     */
    public function testResolveImportUrlsResolvesKeysToFileUrls(): void
    {
        // Create manifest entries
        $appData = new stdClass();
        $appData->file = 'assets/app-abc123.js';
        $appData->imports = ['_vendor-def456.js', '_utils-ghi789.js'];

        $appEntry = ManifestEntry::fromManifestData('src/app.ts', $appData, null);

        $vendorData = new stdClass();
        $vendorData->file = 'assets/vendor-def456.js';

        $vendorEntry = ManifestEntry::fromManifestData('_vendor-def456.js', $vendorData, null);

        $utilsData = new stdClass();
        $utilsData->file = 'assets/utils-ghi789.js';

        $utilsEntry = ManifestEntry::fromManifestData('_utils-ghi789.js', $utilsData, null);

        $collection = new ManifestCollection([$appEntry, $vendorEntry, $utilsEntry]);

        // Resolve the app entry's imports
        $resolvedUrls = $collection->resolveImportUrls($appEntry->imports);

        $expected = [
            '/assets/vendor-def456.js',
            '/assets/utils-ghi789.js',
        ];

        $this->assertSame($expected, $resolvedUrls);
    }

    /**
     * Test resolveImportUrls with build directory
     */
    public function testResolveImportUrlsWithBuildDirectory(): void
    {
        $appData = new stdClass();
        $appData->file = 'assets/app.js';
        $appData->imports = ['_vendor.js'];

        $appEntry = ManifestEntry::fromManifestData('src/app.ts', $appData, 'build');

        $vendorData = new stdClass();
        $vendorData->file = 'assets/vendor.js';

        $vendorEntry = ManifestEntry::fromManifestData('_vendor.js', $vendorData, 'build');

        $collection = new ManifestCollection([$appEntry, $vendorEntry]);

        $resolvedUrls = $collection->resolveImportUrls($appEntry->imports);

        $this->assertSame(['/build/assets/vendor.js'], $resolvedUrls);
    }

    /**
     * Test resolveImportUrls skips keys that don't exist in manifest
     */
    public function testResolveImportUrlsSkipsMissingKeys(): void
    {
        $appData = new stdClass();
        $appData->file = 'assets/app.js';
        $appData->imports = ['_vendor.js', '_missing.js', '_utils.js'];

        $appEntry = ManifestEntry::fromManifestData('src/app.ts', $appData, null);

        $vendorData = new stdClass();
        $vendorData->file = 'assets/vendor.js';

        $vendorEntry = ManifestEntry::fromManifestData('_vendor.js', $vendorData, null);

        $utilsData = new stdClass();
        $utilsData->file = 'assets/utils.js';

        $utilsEntry = ManifestEntry::fromManifestData('_utils.js', $utilsData, null);

        $collection = new ManifestCollection([$appEntry, $vendorEntry, $utilsEntry]);

        $resolvedUrls = $collection->resolveImportUrls($appEntry->imports);

        // Should skip _missing.js
        $expected = [
            '/assets/vendor.js',
            '/assets/utils.js',
        ];

        $this->assertSame($expected, $resolvedUrls);
    }

    /**
     * Test resolveImportUrls with empty imports array
     */
    public function testResolveImportUrlsWithEmptyArray(): void
    {
        $data = new stdClass();
        $data->file = 'assets/app.js';

        $entry = ManifestEntry::fromManifestData('src/app.ts', $data, null);

        $collection = new ManifestCollection([$entry]);

        $resolvedUrls = $collection->resolveImportUrls([]);

        $this->assertSame([], $resolvedUrls);
    }

    /**
     * Test resolveImportUrls resolves nested imports
     */
    public function testResolveImportUrlsWithNestedImports(): void
    {
        // App imports vendor, vendor imports react
        $appData = new stdClass();
        $appData->file = 'assets/app.js';
        $appData->imports = ['_vendor.js'];

        $appEntry = ManifestEntry::fromManifestData('src/app.ts', $appData, null);

        $vendorData = new stdClass();
        $vendorData->file = 'assets/vendor.js';
        $vendorData->imports = ['_react.js'];

        $vendorEntry = ManifestEntry::fromManifestData('_vendor.js', $vendorData, null);

        $reactData = new stdClass();
        $reactData->file = 'assets/react.js';

        $reactEntry = ManifestEntry::fromManifestData('_react.js', $reactData, null);

        $collection = new ManifestCollection([$appEntry, $vendorEntry, $reactEntry]);

        // Resolve app's imports (should get vendor)
        $appImports = $collection->resolveImportUrls($appEntry->imports);
        $this->assertSame(['/assets/vendor.js'], $appImports);

        // Resolve vendor's imports (should get react)
        $vendorImports = $collection->resolveImportUrls($vendorEntry->imports);
        $this->assertSame(['/assets/react.js'], $vendorImports);
    }

    /**
     * Test filterByType works with resolved collections
     */
    public function testFilterByTypeStillWorksAfterResolution(): void
    {
        $jsData = new stdClass();
        $jsData->file = 'assets/app.js';

        $jsEntry = ManifestEntry::fromManifestData('src/app.ts', $jsData, null);

        $cssData = new stdClass();
        $cssData->file = 'assets/app.css';

        $cssEntry = ManifestEntry::fromManifestData('src/app.css', $cssData, null);

        $collection = new ManifestCollection([$jsEntry, $cssEntry]);

        $scripts = $collection->filterByType(AssetType::Script);
        $this->assertCount(1, $scripts);

        $styles = $collection->filterByType(AssetType::Style);
        $this->assertCount(1, $styles);
    }
}
