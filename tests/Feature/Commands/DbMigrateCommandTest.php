<?php

declare(strict_types=1);

use function Pest\Laravel\artisan;

describe('DbMigrateCommand Feature Tests', function (): void {
    it('is registered in the console kernel', function (): void {
        // ARRANGE & ACT
        $commands = app()->make(\Illuminate\Contracts\Console\Kernel::class)->all();

        // ASSERT
        expect($commands)->toHaveKey('db:migrate');
    });

    describe('command forward execution', function (): void {
        it('executes successfully with no options', function (): void {
            // ACT & ASSERT
            artisan('db:migrate')->assertSuccessful();
        });

        it('forwards common options correctly', function (): void {
            // ACT & ASSERT
            // Test pretend option (safe to run in tests)
            artisan('db:migrate', ['--pretend' => true])
                ->assertSuccessful();
        });

        it('forwards multiple options correctly', function (): void {
            // ARRANGE
            $options = [
                '--pretend' => true,
                '--step' => true,
            ];

            // ACT & ASSERT
            // Test combination of options (all safe for tests)
            artisan('db:migrate', $options)->assertSuccessful();
        });

        it('handles path option as array', function (): void {
            // ARRANGE
            $options = [
                '--pretend' => true,
                '--path' => ['database/migrations'],
            ];

            // ACT & ASSERT
            // Test path option which accepts multiple values
            artisan('db:migrate', $options)->assertSuccessful();
        });
    });
});
