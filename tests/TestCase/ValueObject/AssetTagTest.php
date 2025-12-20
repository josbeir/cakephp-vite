<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase\ValueObject;

use CakeVite\Enum\AssetType;
use CakeVite\ValueObject\AssetTag;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * AssetTag Value Object Test
 *
 * Following TDD principles - this test is written BEFORE the value object exists.
 */
class AssetTagTest extends TestCase
{
    /**
     * Test creating script tag
     */
    public function testCreateScriptTag(): void
    {
        $tag = new AssetTag(
            url: '/assets/app.js',
            type: AssetType::Script,
            attributes: ['type' => 'module'],
        );

        $this->assertSame('/assets/app.js', $tag->url);
        $this->assertSame(AssetType::Script, $tag->type);
        $this->assertSame(['type' => 'module'], $tag->attributes);
    }

    /**
     * Test creating style tag
     */
    public function testCreateStyleTag(): void
    {
        $tag = new AssetTag(
            url: '/assets/style.css',
            type: AssetType::Style,
            attributes: ['media' => 'print'],
        );

        $this->assertSame('/assets/style.css', $tag->url);
        $this->assertSame(AssetType::Style, $tag->type);
        $this->assertSame(['media' => 'print'], $tag->attributes);
    }

    /**
     * Test creating tag without attributes
     */
    public function testCreateTagWithoutAttributes(): void
    {
        $tag = new AssetTag(
            url: '/assets/app.js',
            type: AssetType::Script,
        );

        $this->assertSame('/assets/app.js', $tag->url);
        $this->assertSame(AssetType::Script, $tag->type);
        $this->assertSame([], $tag->attributes);
    }

    /**
     * Test AssetTag is readonly (immutable)
     */
    public function testAssetTagIsReadonly(): void
    {
        $tag = new AssetTag('/test.js', AssetType::Script);

        $reflection = new ReflectionClass($tag);
        $this->assertTrue($reflection->isReadOnly());
    }
}
