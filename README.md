[![PHPStan Level 8](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](https://github.com/josbeir/cakephp-vite)
[![Build Status](https://github.com/josbeir/cakephp-vite/actions/workflows/ci.yml/badge.svg)](https://github.com/josbeir/cakephp-vite/actions)
[![codecov](https://codecov.io/github/josbeir/cakephp-vite/graph/badge.svg?token=4VGWJQTWH5)](https://codecov.io/github/josbeir/cakephp-vite)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue.svg)](https://www.php.net/releases/8.2/en.php)
[![CakePHP Version](https://img.shields.io/badge/CakePHP-5.2%2B-red.svg)](https://cakephp.org/)
[![Packagist Downloads](https://img.shields.io/packagist/dt/josbeir/cakephp-vite)](https://packagist.org/packages/josbeir/cakephp-vite)

# CakeVite: Vite Integration for CakePHP

A [Vite.js](https://vitejs.dev/) integration for CakePHP 5.0+ applications. Seamlessly switch between development and production modes with automatic asset tag generation.

> [!NOTE]
> This project is a spiritual successor to [passchn/cakephp-vite](https://github.com/passchn/cakephp-vite), rewritten with modern PHP and a service-oriented architecture. There is no affiliation with the original project.


## Table of Contents

- [Features](#features)
- [Installation](#installation)
  - [Requirements](#requirements)
  - [Installing the Plugin](#installing-the-plugin)
- [Quick Start](#quick-start)
  - [1. Configure Vite](#1-configure-vite)
  - [2. Load the Helper](#2-load-the-helper)
  - [3. Use in Templates](#3-use-in-templates)
- [Configuration](#configuration)
  - [Basic Configuration](#basic-configuration)
  - [Advanced Configuration](#advanced-configuration)
  - [Environment Variables](#environment-variables)
- [Usage](#usage)
  - [Development Mode](#development-mode)
  - [Production Mode](#production-mode)
  - [Plugin Assets](#plugin-assets)
  - [Multiple Entry Points](#multiple-entry-points)
  - [Custom Attributes](#custom-attributes)
  - [Inline Output](#inline-output)
  - [Preloading Assets](#preloading-assets)
  - [Caching](#caching)
  - [Multiple Configurations](#multiple-configurations)
  - [Check Current Mode](#check-current-mode)
- [How It Works](#how-it-works)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## Features

- 🚀 **Automatic Mode Detection**: Seamlessly switches between development and production based on your environment
- 🎯 **Zero Configuration**: Works out of the box with sensible defaults
- 📦 **Plugin Support**: Load assets from CakePHP plugins with ease
- 🔧 **Flexible Configuration**: Customize dev server URLs, manifest paths, and more
- 🎨 **CSS Extraction**: Automatically includes CSS dependencies from JavaScript entries in production
- ⚡ **HMR Support**: Full Hot Module Replacement support in development mode
- 🏎️ **Modulepreload Support**: Automatic preloading of dependencies for faster load times in code-split applications
- ✅ **Type-Safe**: 100% type coverage with PHPStan level 8
- 🧪 **Well Tested**: 95%+ code coverage with comprehensive unit and integration tests

## Installation

### Requirements

- PHP 8.2 or higher
- CakePHP 5.0 or higher
- Vite 2.0 or higher (installed via npm/yarn/pnpm)

### Installing the Plugin

Install via Composer:

```bash
composer require josbeir/cakephp-vite
```

Load the plugin:

```bash
bin/cake plugin load CakeVite
```

## Quick Start

### 1. Configure Vite

Create or update your `vite.config.js`:

```javascript
import { defineConfig } from 'vite';

export default defineConfig({
  root: 'webroot',
  base: '/',
  build: {
    manifest: true,
    outDir: '../webroot',
    rollupOptions: {
      input: {
        main: 'webroot/src/main.js'
      }
    }
  },
  server: {
    origin: 'http://localhost:3000'
  }
});
```

### 2. Load the Helper

In your `src/View/AppView.php`:

```php
public function initialize(): void
{
    parent::initialize();

    $this->loadHelper('CakeVite.Vite');
}
```

### 3. Use in Templates

In your layout file (e.g., `templates/layout/default.php`):

```php
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <title><?= $this->fetch('title') ?></title>

    <?php $this->Vite->script(['files' => ['src/main.js']]); ?>
    <?php $this->Vite->css(['files' => ['src/style.css']]); ?>

    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
    <?= $this->fetch('content') ?>
</body>
</html>
```

**Start Vite dev server:**

```bash
npm run dev  # or: vite
```

**Build for production:**

```bash
npm run build  # or: vite build
```

## Configuration

### Basic Configuration

Create `config/app_vite.php` in your application for custom settings:

```php
<?php
return [
    'CakeVite' => [
        'devServer' => [
            'url' => 'http://localhost:3000',
            'entries' => [
                'script' => ['src/main.js'],
                'style' => ['src/style.css'],
            ],
        ],
    ],
];
```

> [!NOTE]
> **Config entries vs. Helper files parameter:**
> - `devServer.entries`: Default files for **development mode only**. Used when helper is called without `files` option.
> - `files` parameter: Per-call override that works in **both development and production**. In dev, it overrides config entries. In production, it filters manifest entries.
>
> ```php
> // Uses config default (src/main.js) in development
> $this->Vite->script();
>
> // Override for this specific call (works in dev and production)
> $this->Vite->script(['files' => ['src/admin.js']]);
> ```

> [!NOTE]
> The plugin will automatically detect and load `config/app_vite.php` if it exists for backwards compatibility.

### Advanced Configuration

All available configuration options:

```php
<?php
return [
    'CakeVite' => [
        'devServer' => [
            // Development server URL
            'url' => env('VITE_DEV_SERVER_URL', 'http://localhost:3000'),

            // Hostname hints for development mode detection
            'hostHints' => ['localhost', '127.0.0.1', '.test', '.local'],

            // Default entries for development mode
            'entries' => [
                'script' => ['src/main.js'],
                'style' => ['src/style.css'],
            ],
        ],

        'build' => [
            // Path to Vite's manifest.json
            'manifestPath' => WWW_ROOT . 'manifest.json',

            // Output directory (relative to webroot)
            'outDirectory' => false,  // or 'dist', 'build', etc.
        ],

        // Modulepreload support for faster loading (production only)
        // Options: 'none', 'link-tag'
        'preload' => env('VITE_PRELOAD_MODE', 'link-tag'),

        // Persistent caching (production performance optimization)
        'cache' => [
            'config' => env('VITE_CACHE_CONFIG', false),  // CakePHP cache config name
            'development' => false,
        ],

        // Force production mode (ignores environment detection)
        'forceProductionMode' => false,

        // Plugin name for loading plugin assets (null for app assets)
        'plugin' => null,

        // Cookie/query parameter name to force production mode
        'productionModeHint' => 'vprod',

        // View block names for script and CSS tags
        'viewBlocks' => [
            'script' => 'script',
            'css' => 'css',
        ],
    ],
];
```

### Environment Variables

You can use environment variables for configuration:

```bash
# .env
VITE_DEV_SERVER_URL=http://localhost:5173
```

Then reference in your config:

```php
'devServer' => [
    'url' => env('VITE_DEV_SERVER_URL', 'http://localhost:3000'),
],
```

### Using with DDEV

DDEV requires some additional configuration to properly expose the Vite dev server. Here's how to set it up:

**1. Configure `.ddev/config.yaml` to expose port 5173 (or something else):**

```yaml
web_extra_exposed_ports:
  - name: node-vite
    container_port: 5173
    http_port: 5172
    https_port: 5173
```

**2. Create `config/app_vite.php` using DDEV environment variables:**

```php
<?php
return [
    'CakeVite' => [
        'devServer' => [
            'hostHints' => [env('DDEV_HOSTNAME', '')],
            'url' => env('DDEV_PRIMARY_URL') . ':5173',
        ],
    ],
];
```

**3. Update `vite.config.js` to use the DDEV URL:**

```javascript
import { defineConfig } from 'vite';

export default defineConfig({
  root: 'webroot',
  base: '/',
  build: {
    manifest: true,
    outDir: 'webroot/build',
    rollupOptions: {
      input: {
        main: 'webroot/src/main.js'
      }
    }
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    origin: `${process.env.DDEV_PRIMARY_URL.replace(/:\d+$/, "")}:5173`,
    cors: {
      origin: /https?:\/\/([A-Za-z0-9\-\.]+)?(\.ddev\.site)(?::\d+)?$/,
    },
  }
});
```

> [!TIP]
> DDEV automatically sets environment variables like `DDEV_HOSTNAME` and `DDEV_PRIMARY_URL`. Run `ddev exec printenv | grep DDEV` to see all available variables.

## Usage

### Basic Syntax

CakeVite supports both string shorthand and array syntax for loading assets:

**String Shorthand (Simple):**
```php
// Single file - quick and easy
<?php $this->Vite->script('src/main.js'); ?>
<?php $this->Vite->css('src/style.css'); ?>
```

**Array Syntax (Full Featured):**
```php
// Single file with options
<?php $this->Vite->script(['files' => ['src/main.js']]); ?>

// Multiple files
<?php
$this->Vite->script([
    'files' => [
        'src/main.js',
        'src/admin.js',
    ]
]);
?>

// With custom attributes
<?php
$this->Vite->script([
    'files' => ['src/main.js'],
    'attributes' => ['defer' => true]
]);
?>
```

> [!TIP]
> Both `script()` and `css()` methods are void - they append tags to view blocks. Use `<?= $this->fetch('script') ?>` and `<?= $this->fetch('css') ?>` to render them in your layout.

### Development Mode

In development, CakeVite automatically:
- Connects to your Vite dev server
- Includes the Vite client for HMR
- Loads modules as ES modules
- Provides instant hot reloading

```php
// In your template
<?php $this->Vite->script(['files' => ['src/main.js']]); ?>
```

**Output in development:**
```html
<script type="module" src="http://localhost:3000/@vite/client"></script>
<script type="module" src="http://localhost:3000/src/main.js"></script>
```

### Production Mode

In production, CakeVite automatically:
- Reads the build manifest
- Resolves hashed filenames
- Includes dependent CSS files
- Handles legacy browser support

```php
// Same code in template
<?php $this->Vite->script(['files' => ['src/main.js']]); ?>
```

**Output in production:**
```html
<script type="module" src="/assets/main-a1b2c3d4.js"></script>
<link rel="stylesheet" href="/assets/main-e5f6g7h8.css" />
```

### Plugin Assets

Load assets from a CakePHP plugin:

```php
// Load from MyPlugin
<?php
$this->Vite->pluginScript('MyPlugin', true, ['files' => ['src/plugin.js']]);
$this->Vite->pluginCss('MyPlugin', true, ['files' => ['src/plugin.css']]);
?>
```

Or configure globally:

```php
'CakeVite' => [
    'plugin' => 'MyPlugin',
],
```

### Multiple Entry Points

Load multiple files at once:

```php
<?php
$this->Vite->script([
    'files' => [
        'src/main.js',
        'src/admin.js',
        'src/analytics.js',
    ]
]);
?>
```

Filter production assets by pattern:

```php
<?php
// Only load files matching 'admin'
$this->Vite->script([
    'filter' => 'admin'
]);
?>
```

### Custom Attributes

Add custom HTML attributes to generated tags:

```php
<?php
$this->Vite->script([
    'files' => ['src/main.js'],
    'attributes' => [
        'defer' => true,
        'data-turbo-track' => 'reload',
    ]
]);
?>
```

**Output:**
```html
<script type="module" defer data-turbo-track="reload" src="..."></script>
```

### Custom View Blocks

Use custom view blocks for scripts and styles:

```php
<?php
// Append to 'custom_scripts' block instead of default 'script'
$this->Vite->script([
    'files' => ['src/main.js'],
    'block' => 'custom_scripts'
]);
?>

<!-- In layout -->
<?= $this->fetch('custom_scripts') ?>
```

### Inline Output

By default, `script()` and `css()` append tags to view blocks for rendering in layouts.
Set `block => false` to return tags as a string for inline output:

```php
<?php
// Output directly instead of buffering to view blocks
echo $this->Vite->script(['files' => ['src/main.js'], 'block' => false]);
echo $this->Vite->css(['files' => ['src/style.css'], 'block' => false]);
?>
```

This is useful when you need to render assets directly in elements or partials:

```php
<!-- In an element file -->
<?= $this->Vite->css(['files' => ['src/component.css'], 'block' => false]) ?>
<div class="component">
    <!-- Component content -->
</div>
```

### Preloading Assets

CakeVite supports `modulepreload` to improve load times for applications with code splitting. Preloading hints to the browser which modules will be needed soon, allowing parallel downloads.

**Enabled by Default:**
```php
<?php
// Preloading is enabled by default in production
$this->Vite->script(['files' => ['src/main.js']]);
?>
```

**Output in production:**
```html
<!-- Dependencies are preloaded before the main script -->
<link rel="modulepreload" href="/assets/vendor-def456.js">
<link rel="modulepreload" href="/assets/utils-ghi789.js">
<script type="module" src="/assets/app-abc123.js"></script>
```

**Disable Preloading:**
```php
<?php
// Disable preloading for a specific call
$this->Vite->script([
    'files' => ['src/main.js'],
    'preload' => 'none'
]);
?>
```

**Configure Globally:**
```php
// config/app_vite.php
return [
    'CakeVite' => [
        // Options: 'none', 'link-tag'
        'preload' => 'none',  // Disable preloading globally
    ],
];
```

**Environment Variable:**
```bash
# .env
VITE_PRELOAD_MODE=none  # or 'link-tag'
```

> [!NOTE]
> - Preloading only works in **production mode** (development mode uses dev server, no manifest)
> - Uses `rel="modulepreload"` for ES modules
> - Automatically deduplicates URLs to prevent redundant preloads
> - `link-header` mode is reserved for future HTTP/2 header-based preloading
> - Requires Vite's [`build.modulePreload`](https://vite.dev/config/build-options#build-modulepreload) to be enabled (default). If you've disabled it in your Vite config, preloading won't work as import dependencies aren't tracked in the manifest.

**Performance Benefits:**
- Reduces load time by downloading dependencies in parallel
- Particularly beneficial for code-split applications
- Browser can start downloading imports while parsing main script

### Caching

Enable persistent caching of the manifest file in production to eliminate file I/O overhead on every request.

**Enable Caching:**
```php
// config/app_vite.php
return [
    'CakeVite' => [
        'cache' => [
            'config' => 'default',  // Use any CakePHP cache config
        ],
    ],
];
```

**Environment Variable:**
```bash
# .env
VITE_CACHE_CONFIG=default
```

**Cache Invalidation:**
- Automatic: cache key includes manifest file mtime
- When manifest rebuilds, cache automatically invalidates

**Cache in Development:**
```php
'cache' => [
    'config' => 'default',
    'development' => true,  // Enable caching in dev mode
],
```

> [!TIP]
> Enabling caching could improve manifest read performance, particularly when using memory-based solutions such as Redis that avoid file I/O operations.

### Multiple Configurations

Use named configurations for different parts of your application (admin panel, marketing site, etc.).

**Define Named Configs:**
```php
// config/app_vite.php
return [
    'CakeVite' => [
        'devServer' => ['url' => 'http://localhost:3000'],
        'build' => ['manifestPath' => WWW_ROOT . 'manifest.json'],

        'configs' => [
            'admin' => [
                'devServer' => ['url' => 'http://localhost:3001'],
                'build' => [
                    'outDirectory' => 'admin',
                    'manifestPath' => WWW_ROOT . 'admin' . DS . 'manifest.json',
                ],
            ],
            'marketing' => [
                'devServer' => ['url' => 'http://localhost:3002'],
                'build' => ['outDirectory' => 'marketing'],
            ],
        ],
    ],
];
```

**Use in Templates:**
```php
<!-- Use 'admin' config -->
<?php $this->Vite->script(['files' => ['src/admin.ts'], 'config' => 'admin']); ?>
<?php $this->Vite->css(['files' => ['src/admin.css'], 'config' => 'admin']); ?>

<!-- Use 'marketing' config -->
<?php $this->Vite->script(['config' => 'marketing']); ?>
```

**Configuration Inheritance:**
- Named configs inherit from default config
- Override only what you need
- Useful for multi-tenant or multi-section applications

### Check Current Mode

Check if running in development mode:

```php
<?php if ($this->Vite->isDev()): ?>
    <!-- Development-only content -->
    <div class="dev-toolbar">Development Mode</div>
<?php endif; ?>
```

## How It Works

### Mode Detection

CakeVite automatically detects the environment based on:

1. **Force Production Mode**: `forceProductionMode` configuration
2. **Production Hint**: Cookie or query parameter (`vprod` by default)
3. **Host Hints**: Matches hostname against development patterns
4. **Default**: Falls back to production for safety

### Development Mode
- Connects to Vite dev server
- Serves assets from configured dev server URL
- Includes Vite client for HMR
- No manifest file required

### Production Mode
- Reads `manifest.json` generated by Vite
- Maps entry points to hashed output files
- Automatically includes CSS dependencies
- Supports legacy browser builds

## Testing

Run the test suite:

```bash
composer test
```

Run with coverage:

```bash
composer test-coverage
```

Check code standards:

```bash
composer cs-check
composer cs-fix
```

Run static analysis:

```bash
composer phpstan
```

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Write tests for your changes
4. Ensure all quality checks pass (`composer cs-check && composer phpstan && composer test`)
5. Submit a pull request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
