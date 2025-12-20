<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase\Enum;

use CakeVite\Enum\Environment;
use PHPUnit\Framework\TestCase;

/**
 * Environment Enum Test
 *
 * Following TDD principles - this test is written BEFORE the enum exists.
 */
class EnvironmentTest extends TestCase
{
    /**
     * Test that Development enum has correct value
     */
    public function testDevelopmentEnumHasCorrectValue(): void
    {
        $this->assertSame('development', Environment::Development->value);
    }

    /**
     * Test that Production enum has correct value
     */
    public function testProductionEnumHasCorrectValue(): void
    {
        $this->assertSame('production', Environment::Production->value);
    }

    /**
     * Test isDevelopment method returns true for Development
     */
    public function testIsDevelopmentReturnsTrueForDevelopment(): void
    {
        $this->assertTrue(Environment::Development->isDevelopment());
    }

    /**
     * Test isDevelopment method returns false for Production
     */
    public function testIsDevelopmentReturnsFalseForProduction(): void
    {
        $this->assertFalse(Environment::Production->isDevelopment());
    }

    /**
     * Test isProduction method returns true for Production
     */
    public function testIsProductionReturnsTrueForProduction(): void
    {
        $this->assertTrue(Environment::Production->isProduction());
    }

    /**
     * Test isProduction method returns false for Development
     */
    public function testIsProductionReturnsFalseForDevelopment(): void
    {
        $this->assertFalse(Environment::Development->isProduction());
    }

    /**
     * Test enum cases are exhaustive
     */
    public function testEnumHasExactlyTwoCases(): void
    {
        $cases = Environment::cases();
        $this->assertCount(2, $cases);
        $this->assertSame(Environment::Development, $cases[0]);
        $this->assertSame(Environment::Production, $cases[1]);
    }
}
