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
        return new self($this->filter(fn(ManifestEntry $entry) => $entry->getAssetType() === $type));
    }

    /**
     * Filter entries only (exclude non-entry chunks)
     */
    public function filterEntries(): self
    {
        return new self($this->filter(fn(ManifestEntry $entry) => $entry->isEntry));
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

        return new self($this->filter(function (ManifestEntry $entry) use ($patterns, $property) {
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

        usort($items, function (ManifestEntry $a, ManifestEntry $b) {
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
}
