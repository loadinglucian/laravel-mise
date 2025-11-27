<?php

declare(strict_types=1);

namespace Bigpixelrocket\LaravelBuffet;

use Bigpixelrocket\LaravelBuffet\Commands\BuffetCommand;
use Bigpixelrocket\LaravelBuffet\Commands\DbMigrateCommand;
use Illuminate\Support\ServiceProvider;

class BuffetServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DbMigrateCommand::class,
                BuffetCommand::class,
            ]);
        }
    }

    #[\Override]
    public function register(): void
    {
        //
        // Package Configuration Registration
        // -------------------------------------------------------------------------------

        // Register internal package configurations
        // These are not meant to be published/customized by users
        $this->mergeConfigFrom(
            __DIR__.'/../config/composer-packages.php',
            'laravel-buffet.composer-packages'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../config/npm-packages.php',
            'laravel-buffet.npm-packages'
        );
    }
}
