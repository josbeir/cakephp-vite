<?php
declare(strict_types=1);

namespace CakeVite\Enum;

/**
 * ScriptType Enum
 *
 * Represents the type of JavaScript module (module, legacy, polyfill).
 * Used to determine load order and HTML attributes (type="module", nomodule, etc).
 */
enum ScriptType: string
{
    case Module = 'module';
    case Legacy = 'legacy';
    case Polyfill = 'polyfill';

    /**
     * Check if this is a Module script (ES modules)
     */
    public function isModule(): bool
    {
        return $this === self::Module;
    }
}
