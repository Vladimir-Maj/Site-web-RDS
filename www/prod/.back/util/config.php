<?php

declare(strict_types=1);

// ============================================================================
// Global configuration
// ============================================================================

if (!defined('CDN_URL')) {
    define('CDN_URL', 'https://cdn.stageflow.fr');
}

if (!defined('SITE_URL')) {
    define('SITE_URL', 'https://prod.stageflow.fr');
}

if (!defined('APP_ENV')) {
    define('APP_ENV', 'development');
}

// ============================================================================
// Core bootstrap
// ============================================================================

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/db_connect.php';

// ============================================================================
// Lightweight project autoloader for legacy .back structure
// ============================================================================

spl_autoload_register(function (string $className): void {
    $root = realpath(__DIR__ . '/../../');

    if ($root === false) {
        return;
    }

    $sources = [
        $root . '/.back/models/',
        $root . '/.back/repository/',
        $root . '/.back/controllers/',
        $root . '/.back/util/',
    ];

    $relativeClass = str_replace('\\', DIRECTORY_SEPARATOR, ltrim($className, '\\'));
    $shortClass    = basename($relativeClass);

    foreach ($sources as $source) {
        $candidates = [
            $source . $relativeClass . '.php',
            $source . $shortClass . '.php',
        ];

        foreach ($candidates as $file) {
            if (is_file($file)) {
                require_once $file;
                return;
            }
        }
    }
});
