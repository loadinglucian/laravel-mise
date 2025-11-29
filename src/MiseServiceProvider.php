<?php

declare(strict_types=1);

namespace Bigpixelrocket\LaravelMise;

use Bigpixelrocket\LaravelMise\Commands\DbMigrateCommand;
use Bigpixelrocket\LaravelMise\Commands\MiseCommand;
use Illuminate\Support\ServiceProvider;

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
        $this->mergeConfigFrom(
            __DIR__.'/../config/composer-packages.php',
            'laravel-mise.composer-packages'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../config/npm-packages.php',
            'laravel-mise.npm-packages'
        );
    }
}
