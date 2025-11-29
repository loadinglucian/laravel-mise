<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withCache(
        '/tmp/rector',
        FileCacheStorage::class,
    )
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/payload',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withSkip([
        __DIR__.'/payload/tests/CICanary.php',
        __DIR__.'/tests/CICanary.php',
    ]);
