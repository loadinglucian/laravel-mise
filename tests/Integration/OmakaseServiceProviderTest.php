<?php

declare(strict_types=1);

namespace Tests\Integration;

use Bigpixelrocket\LaravelOmakase\Commands\OmakaseCommand;
use Bigpixelrocket\LaravelOmakase\OmakaseServiceProvider;
use Illuminate\Support\Facades\Artisan;

describe('OmakaseServiceProvider Integration Tests', function (): void {
    it('is registered as a service provider in the package', function (): void {
        // ARRANGE
        $expectedProviderClass = OmakaseServiceProvider::class;

        // ACT
        $loadedProviders = app()->getLoadedProviders();

        // ASSERT
        expect($loadedProviders)
            ->toHaveKey($expectedProviderClass)
            ->and($loadedProviders[$expectedProviderClass])->toBe(true);
    });

    it('registers the OmakaseCommand when running in console', function (): void {
        // ARRANGE
        $expectedCommandName = 'laravel:omakase';

        // ACT
        $provider = new OmakaseServiceProvider(app());
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
        expect($registeredCommands[$expectedCommandName])->toBeInstanceOf(OmakaseCommand::class);
    });
});
