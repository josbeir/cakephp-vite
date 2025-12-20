<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase\Enum;

use CakeVite\Enum\AssetType;
use PHPUnit\Framework\TestCase;

/**
 * AssetType Enum Test
 *
 * Following TDD principles - this test is written BEFORE the enum exists.
 */
class AssetTypeTest extends TestCase
{
    /**
     * Test that Script enum has correct value
     */
    public function testScriptEnumHasCorrectValue(): void
    {
        $this->assertSame('script', AssetType::Script->value);
    }

    /**
     * Test that Style enum has correct value
     */
    public function testStyleEnumHasCorrectValue(): void
    {
        $this->assertSame('style', AssetType::Style->value);
    }

    /**
     * Test isScript method returns true for Script
     */
    public function testIsScriptReturnsTrueForScript(): void
    {
        $this->assertTrue(AssetType::Script->isScript());
    }

    /**
     * Test isScript method returns false for Style
     */
    public function testIsScriptReturnsFalseForStyle(): void
    {
        $this->assertFalse(AssetType::Style->isScript());
    }

    /**
     * Test isStyle method returns true for Style
     */
    public function testIsStyleReturnsTrueForStyle(): void
    {
        $this->assertTrue(AssetType::Style->isStyle());
    }

    /**
     * Test isStyle method returns false for Script
     */
    public function testIsStyleReturnsFalseForScript(): void
    {
        $this->assertFalse(AssetType::Script->isStyle());
    }

    /**
     * Test enum cases are exhaustive
     */
    public function testEnumHasExactlyTwoCases(): void
    {
        $cases = AssetType::cases();
        $this->assertCount(2, $cases);
        $this->assertSame(AssetType::Script, $cases[0]);
        $this->assertSame(AssetType::Style, $cases[1]);
    }
}
