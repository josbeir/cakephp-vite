<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase;

use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\TestSuite\TestCase;
use CakeVite\CakeVitePlugin;

/**
 * CakeVitePlugin Test
 */
class CakeVitePluginTest extends TestCase
{
    private CakeVitePlugin $plugin;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->plugin = new CakeVitePlugin();
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Configure::delete('CakeVite');
    }

    /**
     * Test that plugin loads default configuration
     */
    public function testBootstrapLoadsDefaultConfiguration(): void
    {
        // Remove app_vite.php temporarily to test default config only
        $appViteFile = ROOT . DS . 'config' . DS . 'app_vite.php';
        $appViteBackup = $appViteFile . '.bak';
        if (file_exists($appViteFile)) {
            rename($appViteFile, $appViteBackup);
        }

        try {
            Configure::delete('CakeVite');

            $app = $this->createMock(PluginApplicationInterface::class);
            $this->plugin->bootstrap($app);

            $this->assertTrue(Configure::check('CakeVite'));
            $config = Configure::read('CakeVite');
            $this->assertIsArray($config);
            $this->assertArrayHasKey('devServer', $config);
            $this->assertArrayHasKey('build', $config);
        } finally {
            // Restore app_vite.php if it was backed up
            if (file_exists($appViteBackup)) {
                rename($appViteBackup, $appViteFile);
            }
        }
    }

    /**
     * Test backwards compatibility: loading app_vite.php
     */
    public function testBootstrapLoadsAppViteConfiguration(): void
    {
        Configure::delete('CakeVite');

        $app = $this->createMock(PluginApplicationInterface::class);
        $this->plugin->bootstrap($app);

        // Check that app_vite.php configuration was loaded
        $this->assertTrue(Configure::check('CakeVite'));
        $config = Configure::read('CakeVite');

        // Verify custom configuration from app_vite.php
        $this->assertSame('http://localhost:5173', $config['devServer']['url']);
        $this->assertSame(['custom-app.ts'], $config['devServer']['entries']['script']);
    }

    /**
     * Test that app_vite.php is not required
     */
    public function testBootstrapWorksWithoutAppViteFile(): void
    {
        // Temporarily rename app_vite.php to test without it
        $appViteFile = ROOT . DS . 'config' . DS . 'app_vite.php';
        $appViteBackup = $appViteFile . '.bak';
        if (file_exists($appViteFile)) {
            rename($appViteFile, $appViteBackup);
        }

        try {
            Configure::delete('CakeVite');

            $app = $this->createMock(PluginApplicationInterface::class);
            $this->plugin->bootstrap($app);

            // Should still load default configuration
            $this->assertTrue(Configure::check('CakeVite'));
            $config = Configure::read('CakeVite');
            $this->assertArrayHasKey('devServer', $config);
        } finally {
            // Restore app_vite.php
            if (file_exists($appViteBackup)) {
                rename($appViteBackup, $appViteFile);
            }
        }
    }
}
