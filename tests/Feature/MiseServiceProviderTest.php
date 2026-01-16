<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use LaravelMise\MiseServiceProvider;

describe('MiseServiceProvider Feature Tests', function (): void {
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
        // ARRANGE & ACT
        $provider = new MiseServiceProvider(app());
        $provider->register();
        $provider->boot();
        $registeredCommands = Artisan::all();

        // ASSERT
        expect($registeredCommands)
            ->toHaveKey('laravel:mise')
            ->and($registeredCommands['laravel:mise'])
            ->getName()->toBe('laravel:mise');
    });
});
