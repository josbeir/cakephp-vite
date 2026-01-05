<?php
declare(strict_types=1);

namespace CakeVite\ValueObject;

use Cake\Collection\Collection;
use CakeVite\Enum\AssetType;

/**
 * Collection of manifest entries with filtering capabilities
 *
 * Extends CakePHP Collection for rich manipulation methods.
 *
 * @extends \Cake\Collection\Collection<int, \CakeVite\ValueObject\ManifestEntry>
 */
final class ManifestCollection extends Collection
{
    /**
     * Filter by asset type
     *
     * @param \CakeVite\Enum\AssetType $type Asset type to filter by
     */
    public function filterByType(AssetType $type): self
    {
        return new self($this->filter(fn(ManifestEntry $entry): bool => $entry->getAssetType() === $type));
    }

    /**
     * Filter entries only (exclude non-entry chunks)
     */
    public function filterEntries(): self
    {
        return new self($this->filter(fn(ManifestEntry $entry): bool => $entry->isEntry));
    }

    /**
     * Filter by pattern matching
     *
     * @param array<string>|string $patterns Pattern(s) to match
     * @param string $property Property to match against (src, file, key)
     */
    public function filterByPattern(string|array $patterns, string $property = 'src'): self
    {
        $patterns = (array)$patterns;

        return new self($this->filter(function (ManifestEntry $entry) use ($patterns, $property): bool {
            foreach ($patterns as $pattern) {
                if ($entry->matches($pattern, $property)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * Sort entries by load order (polyfills first, then legacy, then modules)
     */
    public function sortByLoadOrder(): self
    {
        $items = $this->toArray();

        usort($items, function (ManifestEntry $a, ManifestEntry $b): int {
            $aType = $a->getScriptType();
            $bType = $b->getScriptType();

            $aValue = match ($aType?->value) {
                'polyfill' => 0,
                'legacy' => 1,
                'module' => 2,
                default => 3,
            };

            $bValue = match ($bType?->value) {
                'polyfill' => 0,
                'legacy' => 1,
                'module' => 2,
                default => 3,
            };

            return $aValue <=> $bValue;
        });

        return new self($items);
    }

    /**
     * Find entry by manifest key
     *
     * @param string $key Manifest key to search for
     * @return \CakeVite\ValueObject\ManifestEntry|null Entry if found, null otherwise
     */
    public function findByKey(string $key): ?ManifestEntry
    {
        foreach ($this as $entry) {
            if ($entry->key === $key) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Resolve import keys to file URLs
     *
     * In Vite's manifest, the `imports` array contains keys that reference
     * other entries in the manifest, not direct file paths. This method
     * looks up each import key and returns the actual file URL.
     *
     * Performance: Uses indexBy() for O(n+m) complexity instead of O(n*m)
     * with repeated linear searches.
     *
     * @param array<string> $importKeys Import keys from manifest entry
     * @return array<string> Resolved file URLs
     */
    public function resolveImportUrls(array $importKeys): array
    {
        if ($importKeys === []) {
            return [];
        }

        // Create keyed lookup for O(1) access - O(n) operation done once
        $keyedManifest = $this->indexBy(fn(ManifestEntry $entry): string => $entry->key)->toArray();

        $urls = [];
        foreach ($importKeys as $key) {
            if (isset($keyedManifest[$key]) && $keyedManifest[$key] instanceof ManifestEntry) {
                $urls[] = $keyedManifest[$key]->getUrl();
            }
        }

        return $urls;
    }
}
