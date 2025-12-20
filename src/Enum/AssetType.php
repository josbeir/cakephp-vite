<?php
declare(strict_types=1);

namespace CakeVite\Enum;

/**
 * AssetType Enum
 *
 * Represents the type of asset being processed (script or style).
 * Used to determine rendering strategy and HTML tag generation.
 */
enum AssetType: string
{
    case Script = 'script';
    case Style = 'style';

    /**
     * Check if this is a Script asset
     */
    public function isScript(): bool
    {
        return $this === self::Script;
    }

    /**
     * Check if this is a Style asset
     */
    public function isStyle(): bool
    {
        return $this === self::Style;
    }
}
