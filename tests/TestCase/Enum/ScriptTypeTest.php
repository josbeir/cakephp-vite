<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase\Enum;

use CakeVite\Enum\ScriptType;
use PHPUnit\Framework\TestCase;

/**
 * ScriptType Enum Test
 *
 * Following TDD principles - this test is written BEFORE the enum exists.
 */
class ScriptTypeTest extends TestCase
{
    /**
     * Test that Module enum has correct value
     */
    public function testModuleEnumHasCorrectValue(): void
    {
        $this->assertSame('module', ScriptType::Module->value);
    }

    /**
     * Test that Legacy enum has correct value
     */
    public function testLegacyEnumHasCorrectValue(): void
    {
        $this->assertSame('legacy', ScriptType::Legacy->value);
    }

    /**
     * Test that Polyfill enum has correct value
     */
    public function testPolyfillEnumHasCorrectValue(): void
    {
        $this->assertSame('polyfill', ScriptType::Polyfill->value);
    }

    /**
     * Test isModule method returns true for Module
     */
    public function testIsModuleReturnsTrueForModule(): void
    {
        $this->assertTrue(ScriptType::Module->isModule());
    }

    /**
     * Test isModule method returns false for Legacy
     */
    public function testIsModuleReturnsFalseForLegacy(): void
    {
        $this->assertFalse(ScriptType::Legacy->isModule());
    }

    /**
     * Test isModule method returns false for Polyfill
     */
    public function testIsModuleReturnsFalseForPolyfill(): void
    {
        $this->assertFalse(ScriptType::Polyfill->isModule());
    }

    /**
     * Test enum cases are exhaustive
     */
    public function testEnumHasExactlyThreeCases(): void
    {
        $cases = ScriptType::cases();
        $this->assertCount(3, $cases);
        $this->assertSame(ScriptType::Module, $cases[0]);
        $this->assertSame(ScriptType::Legacy, $cases[1]);
        $this->assertSame(ScriptType::Polyfill, $cases[2]);
    }
}
