<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase\Enum;

use CakeVite\Enum\PreloadMode;
use PHPUnit\Framework\TestCase;

class PreloadModeTest extends TestCase
{
    public function testPreloadModeHasCorrectValues(): void
    {
        $this->assertSame('none', PreloadMode::None->value);
        $this->assertSame('link-tag', PreloadMode::LinkTag->value);
        $this->assertSame('link-header', PreloadMode::LinkHeader->value);
    }

    public function testIsNoneReturnsTrueForNone(): void
    {
        $this->assertTrue(PreloadMode::None->isNone());
        $this->assertFalse(PreloadMode::LinkTag->isNone());
        $this->assertFalse(PreloadMode::LinkHeader->isNone());
    }

    public function testIsLinkTagReturnsTrueForLinkTag(): void
    {
        $this->assertTrue(PreloadMode::LinkTag->isLinkTag());
        $this->assertFalse(PreloadMode::None->isLinkTag());
        $this->assertFalse(PreloadMode::LinkHeader->isLinkTag());
    }

    public function testIsLinkHeaderReturnsTrueForLinkHeader(): void
    {
        $this->assertTrue(PreloadMode::LinkHeader->isLinkHeader());
        $this->assertFalse(PreloadMode::None->isLinkHeader());
        $this->assertFalse(PreloadMode::LinkTag->isLinkHeader());
    }
}
