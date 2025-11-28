<?php

declare(strict_types=1);

namespace Tests\Integration;

use Bigpixelrocket\LaravelMise\Commands\MiseCommand;
use Bigpixelrocket\LaravelMise\MiseServiceProvider;
use Illuminate\Support\Facades\Artisan;

describe('MiseServiceProvider Integration Tests', function (): void {
    it('is registered as a service provider in the package', function (): void {
        // ARRANGE
        $expectedProviderClass = MiseServiceProvider::class;

        // ACT
        $loadedProviders = app()->getLoadedProviders();

        // ASSERT
        expect($loadedProviders)
            ->toHaveKey($expectedProviderClass)
            ->and($loadedProviders[$expectedProviderClass])->toBe(true);
    });

    it('registers the MiseCommand when running in console', function (): void {
        // ARRANGE
        $expectedCommandName = 'laravel:mise';

        // ACT
        $provider = new MiseServiceProvider(app());
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
        expect($registeredCommands[$expectedCommandName])->toBeInstanceOf(MiseCommand::class);
    });
});
