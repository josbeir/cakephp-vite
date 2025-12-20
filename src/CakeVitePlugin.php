<?php
declare(strict_types=1);

namespace CakeVite;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;

/**
 * CakeVite Plugin
 *
 * Provides Vite.js integration for CakePHP applications.
 */
class CakeVitePlugin extends BasePlugin
{
    /**
     * Load plugin configuration
     *
     * @param \Cake\Core\PluginApplicationInterface $app Application instance
     * @phpstan-param \Cake\Core\PluginApplicationInterface<mixed> $app
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        // Load default configuration if not already set
        if (!Configure::check('CakeVite')) {
            Configure::load('CakeVite.vite');
        }

        // Backwards compatibility: Load app_vite.php if it exists
        if (file_exists(ROOT . DS . 'config' . DS . 'app_vite.php')) {
            Configure::load('app_vite');
        }
    }
}
