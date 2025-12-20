<?php
declare(strict_types=1);

namespace CakeVite\Enum;

/**
 * Environment Enum
 *
 * Represents the current environment mode (development or production).
 * Used throughout the plugin to determine asset serving strategy.
 */
enum Environment: string
{
    case Development = 'development';
    case Production = 'production';

    /**
     * Check if this is the Development environment
     */
    public function isDevelopment(): bool
    {
        return $this === self::Development;
    }

    /**
     * Check if this is the Production environment
     */
    public function isProduction(): bool
    {
        return $this === self::Production;
    }
}
