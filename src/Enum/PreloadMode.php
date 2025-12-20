<?php
declare(strict_types=1);

namespace CakeVite\Enum;

/**
 * Preload mode for module dependencies
 */
enum PreloadMode: string
{
    case None = 'none';
    case LinkTag = 'link-tag';
    case LinkHeader = 'link-header';

    /**
     * Check if mode is None
     */
    public function isNone(): bool
    {
        return $this === self::None;
    }

    /**
     * Check if mode is LinkTag
     */
    public function isLinkTag(): bool
    {
        return $this === self::LinkTag;
    }

    /**
     * Check if mode is LinkHeader
     */
    public function isLinkHeader(): bool
    {
        return $this === self::LinkHeader;
    }
}
