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
     */
    public function __construct(
        public string $url,
        public AssetType $type,
        public array $attributes = [],
    ) {
    }
}
