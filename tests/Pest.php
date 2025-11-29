<?php

declare(strict_types=1);

// -------------------------------------------------------------------------------
//
// Pest Configuration and Test Utilities
//
// -------------------------------------------------------------------------------
//
// Centralized Pest setup and shared helpers used across the test suite.
// Keep this file focused on global configuration, custom expectations,
// and lightweight helper functions.

//
// Test Case Binding
// -------------------------------------------------------------------------------
uses(Tests\TestCase::class)
    ->in('Arch', 'Feature', 'Unit');

//
// Test Helpers
// -------------------------------------------------------------------------------

/**
 * Create a temporary directory with automatic cleanup tracking.
 */
function createTempDirectory(string $prefix = 'laravel_mise_test_'): string
{
    $dir = sys_get_temp_dir().'/'.$prefix.uniqid();
    \Illuminate\Support\Facades\File::ensureDirectoryExists($dir);

    return $dir;
}

/**
 * Set application base path temporarily with automatic restoration.
 */
function withTemporaryBasePath(string $tempPath, callable $callback): mixed
{
    $app = app();
    $original = $app->basePath();

    try {
        $app->setBasePath($tempPath);

        return $callback();
    } finally {
        $app->setBasePath($original);
    }
}
