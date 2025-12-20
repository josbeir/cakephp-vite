<?php
declare(strict_types=1);

/**
 * CakeVite Plugin Configuration
 *
 * Default configuration values for the CakeVite plugin.
 */
return [
    'CakeVite' => [
        'devServer' => [
            'url' => env('VITE_DEV_SERVER_URL', 'http://localhost:3000'),
            'hostHints' => ['localhost', '127.0.0.1', '.test', '.local'],
            'entries' => [
                'script' => [],  // e.g. ['webroot/src/main.ts']
                'style' => [],   // e.g. ['webroot/src/style.css']
            ],
        ],
        'build' => [
            'manifestPath' => WWW_ROOT . 'manifest.json',
            'outDirectory' => false,  // or 'dist'
        ],
        'forceProductionMode' => false,
        'plugin' => null,  // Set to plugin name when using plugin assets
        'productionModeHint' => 'vprod',  // Cookie/query param name
        'preload' => env('VITE_PRELOAD_MODE', 'link-tag'),  // 'none', 'link-tag', 'link-header'
        'viewBlocks' => [
            'script' => 'script',
            'css' => 'css',
        ],
    ],
];
