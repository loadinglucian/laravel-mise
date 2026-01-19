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
        __DIR__.'/app',
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withSkip([
        '*/tests/CICanary.php',
    ]);
