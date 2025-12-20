<?php
declare(strict_types=1);

namespace CakeVite\ValueObject;

use CakeVite\Enum\AssetType;

/**
 * Represents a generated HTML asset tag
 *
 * Immutable value object using constructor property promotion and readonly.
 */
final readonly class AssetTag
{
    /**
     * Constructor with property promotion
     *
     * @param string $url Asset URL
     * @param \CakeVite\Enum\AssetType $type Asset type (script or style)
     * @param array<string, mixed> $attributes HTML attributes for the tag
     * @param bool $isPreload Whether this tag is a preload tag
     * @param string|null $preloadType Type of preload ('preload' or 'modulepreload')
     */
    public function __construct(
        public string $url,
        public AssetType $type,
        public array $attributes = [],
        public bool $isPreload = false,
        public ?string $preloadType = null,
    ) {
    }
}
