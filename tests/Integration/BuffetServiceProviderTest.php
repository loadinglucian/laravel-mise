<?php

declare(strict_types=1);

namespace Tests\Integration;

use Bigpixelrocket\LaravelBuffet\BuffetServiceProvider;
use Bigpixelrocket\LaravelBuffet\Commands\BuffetCommand;
use Illuminate\Support\Facades\Artisan;

describe('BuffetServiceProvider Integration Tests', function (): void {
    it('is registered as a service provider in the package', function (): void {
        // ARRANGE
        $expectedProviderClass = BuffetServiceProvider::class;

        // ACT
        $loadedProviders = app()->getLoadedProviders();

        // ASSERT
        expect($loadedProviders)
            ->toHaveKey($expectedProviderClass)
            ->and($loadedProviders[$expectedProviderClass])->toBe(true);
    });

    it('registers the BuffetCommand when running in console', function (): void {
        // ARRANGE
        $expectedCommandName = 'laravel:buffet';

        // ACT
        $provider = new BuffetServiceProvider(app());
        $provider->register();
        $provider->boot();
        $registeredCommands = Artisan::all();

        // ASSERT
        expect($registeredCommands)
            ->toHaveKey($expectedCommandName)
            ->and($registeredCommands[$expectedCommandName])
            ->getName()->toBe($expectedCommandName)
            ->and($registeredCommands[$expectedCommandName]);

        // Verify the specific command instance is registered
        expect($registeredCommands[$expectedCommandName])->toBeInstanceOf(BuffetCommand::class);
    });
});
