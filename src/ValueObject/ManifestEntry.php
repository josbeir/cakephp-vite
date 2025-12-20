<?php
declare(strict_types=1);

namespace CakeVite\ValueObject;

use CakeVite\Enum\AssetType;
use CakeVite\Enum\ScriptType;
use stdClass;

/**
 * Represents a single entry in the Vite manifest
 *
 * Immutable value object using constructor property promotion and readonly.
 */
final readonly class ManifestEntry
{
    /**
     * Constructor with property promotion
     *
     * @param string $key Manifest entry key
     * @param string $file Built file path
     * @param string $src Source file path
     * @param bool $isEntry Whether this is an entry point
     * @param array<string> $css Associated CSS files
     * @param array<string> $imports Imported chunks
     * @param string|null $buildDirectory Build output directory
     */
    public function __construct(
        public string $key,
        public string $file,
        public string $src,
        public bool $isEntry,
        public array $css,
        public array $imports,
        public ?string $buildDirectory,
    ) {
    }

    /**
     * Create from manifest data object
     *
     * @param string $key Manifest entry key
     * @param \stdClass $data Manifest entry data
     * @param string|null $buildDirectory Build directory
     */
    public static function fromManifestData(string $key, stdClass $data, ?string $buildDirectory): self
    {
        return new self(
            key: $key,
            file: $data->file,
            src: $data->src ?? $key,
            isEntry: $data->isEntry ?? false,
            css: $data->css ?? [],
            imports: $data->imports ?? [],
            buildDirectory: $buildDirectory,
        );
    }

    /**
     * Get the asset type (script or style)
     */
    public function getAssetType(): AssetType
    {
        return str_ends_with($this->file, '.css')
            ? AssetType::Style
            : AssetType::Script;
    }

    /**
     * Get the script type (module, legacy, or polyfill)
     * Returns null for non-script assets
     */
    public function getScriptType(): ?ScriptType
    {
        if ($this->getAssetType() !== AssetType::Script) {
            return null;
        }

        if (str_contains($this->file, 'polyfill')) {
            return ScriptType::Polyfill;
        }

        if (str_contains($this->file, 'legacy')) {
            return ScriptType::Legacy;
        }

        return ScriptType::Module;
    }

    /**
     * Get the full URL for this asset
     */
    public function getUrl(): string
    {
        $prefix = $this->buildDirectory ? '/' . trim($this->buildDirectory, '/') : '';

        return $prefix . '/' . ltrim($this->file, '/');
    }

    /**
     * Get URLs for dependent CSS files
     *
     * @return array<string>
     */
    public function getDependentCssUrls(): array
    {
        return array_map(
            fn(string $file): string => (
                $this->buildDirectory ? '/' . trim($this->buildDirectory, '/') : ''
            ) . '/' . ltrim($file, '/'),
            $this->css,
        );
    }

    /**
     * Get URLs for imported modules
     *
     * @return array<string>
     */
    public function getImportUrls(): array
    {
        return array_map(
            fn(string $importKey): string => (
                $this->buildDirectory ? '/' . trim($this->buildDirectory, '/') : ''
            ) . '/' . ltrim($importKey, '/'),
            $this->imports,
        );
    }

    /**
     * Check if this entry matches a pattern
     *
     * @param string $pattern Pattern to match
     * @param string $property Property to search (src, file, or key)
     */
    public function matches(string $pattern, string $property = 'src'): bool
    {
        $value = match ($property) {
            'src' => $this->src,
            'file' => $this->file,
            'key' => $this->key,
            default => null,
        };

        return $value !== null && str_contains($value, $pattern);
    }
}
