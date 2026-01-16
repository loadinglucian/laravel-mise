<?php

declare(strict_types=1);

namespace LaravelMise;

use Illuminate\Support\ServiceProvider;
use LaravelMise\Commands\DbMigrateCommand;
use LaravelMise\Commands\MiseCommand;
use LaravelMise\Services\ComposerJsonService;
use LaravelMise\Services\NodeDetector;
use LaravelMise\Services\PayloadService;
use LaravelMise\Services\ProcessService;

class MiseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DbMigrateCommand::class,
                MiseCommand::class,
            ]);
        }
    }

    #[\Override]
    public function register(): void
    {
        //
        // Package Configuration Registration
        // ----

        $this->mergeConfigFrom(
            __DIR__.'/../config/composer-packages.php',
            'laravel-mise.composer-packages'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../config/node-packages.php',
            'laravel-mise.node-packages'
        );

        //
        // Service Registration
        // ----

        $this->app->singleton(ProcessService::class);
        $this->app->singleton(ComposerJsonService::class);
        $this->app->singleton(PayloadService::class);
        $this->app->singleton(NodeDetector::class);
    }
}
