<?php
declare(strict_types=1);

namespace CakeVite\Service;

use Cake\Http\ServerRequest;
use CakeVite\Enum\Environment;
use CakeVite\ValueObject\ViteConfig;

/**
 * Determines the current environment (development vs production)
 *
 * Uses constructor property promotion with readonly for immutability.
 */
final class EnvironmentService
{
    /**
     * Constructor with property promotion
     *
     * @param \Cake\Http\ServerRequest $request Current HTTP request
     */
    public function __construct(
        private readonly ServerRequest $request,
    ) {
    }

    /**
     * Detect current environment based on configuration and request
     *
     * Priority order:
     * 1. Force production mode via configuration
     * 2. Production mode hint (cookie or query param)
     * 3. Development host hints
     * 4. Default to production for safety
     *
     * @param \CakeVite\ValueObject\ViteConfig $config Configuration
     * @return \CakeVite\Enum\Environment Detected environment
     */
    public function detect(ViteConfig $config): Environment
    {
        // Force production mode via configuration
        if ($config->forceProductionMode) {
            return Environment::Production;
        }

        // Check for production mode hint (cookie or query param)
        if ($this->hasProductionModeHint($config->productionModeHint)) {
            return Environment::Production;
        }

        // Check if running on development host
        if ($this->isDevelopmentHost($config->devServerHostHints)) {
            return Environment::Development;
        }

        // Default to production for safety
        return Environment::Production;
    }

    /**
     * Check if production mode hint exists in cookie or query
     *
     * @param string $hint Production mode hint parameter name
     */
    private function hasProductionModeHint(string $hint): bool
    {
        return (bool)($this->request->getCookie($hint) ?? $this->request->getQuery($hint));
    }

    /**
     * Check if current host matches development host hints
     *
     * @param array<string> $hostHints Development host patterns
     */
    private function isDevelopmentHost(array $hostHints): bool
    {
        $host = $this->request->host();
        if ($host === null) {
            return false;
        }

        $host = strtolower($host);

        foreach ($hostHints as $hint) {
            if (str_contains($host, strtolower($hint))) {
                return true;
            }
        }

        return false;
    }
}
