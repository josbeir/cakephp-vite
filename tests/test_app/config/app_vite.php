<?php
declare(strict_types=1);

/**
 * Test fixture for backwards compatible app_vite.php configuration
 */
return [
    'CakeVite' => [
        'devServer' => [
            'url' => 'http://localhost:5173',
            'entries' => [
                'script' => ['custom-app.ts'],
            ],
        ],
    ],
];
