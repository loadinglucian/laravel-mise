<?php

//
// MiseCommand Feature Tests - End-to-end command behavior
// ----

declare(strict_types=1);

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use LaravelMise\Commands\MiseCommand;
use Tests\Support\MiseCommandHelpers;

uses(MiseCommandHelpers::class);

describe('MiseCommand Feature Tests', function (): void {
    beforeEach(function (): void {
        Process::fake();
        $this->setTestPackageConfigs();
    });

    it('has correct command signature', function (): void {
        // ARRANGE
        $command = app()->make(MiseCommand::class);
        $definition = $command->getDefinition();

        // ACT & ASSERT
        expect($command->getName())->toBe('laravel:mise')
            ->and($definition->getOptions())->toHaveKey('force')
            ->and($definition->getOption('force')->getDefault())->toBeFalse();
    });

    it('runs all installation phases', function (): void {
        // ARRANGE & ACT
        $this->runMise()
            ->expectsOutputToContain('Installing Composer Packages')
            ->expectsOutputToContain('Copying files')
            ->assertSuccessful();

        // ASSERT
        $this->assertCommandRan('composer update');
    });

    //
    // File Operations
    // ----

    describe('file operations', function (): void {
        beforeEach(function (): void {
            Storage::fake('local');
        });

        it('copies files with force option', function (): void {
            // ARRANGE
            Storage::put('test-config.txt', 'original content');

            // ACT
            $this->runMise(['--force' => true])
                ->expectsOutputToContain('Copying files')
                ->assertSuccessful();
        });

        it('copies files without force option', function (): void {
            // ACT
            $this->runMise()
                ->expectsOutputToContain('Copying files')
                ->assertSuccessful();
        });
    });

    //
    // Error Handling
    // ----

    describe('error handling', function (): void {
        it('fails when composer update fails', function (): void {
            // ARRANGE
            Process::fake(['*' => Process::result('', 'Network error', 1)]);

            // ACT
            $this->runMise()
                ->expectsOutputToContain('Installing Composer Packages')
                ->assertFailed();

            // ASSERT
            $this->assertCommandRan('composer update');
        });

        it('fails when npm update fails', function (): void {
            // ARRANGE
            Process::fake([
                '*' => function (PendingProcess $process) {
                    $command = $this->extractCommand($process);

                    // Composer commands succeed
                    if (str_contains($command, 'composer')) {
                        return Process::result('', '', 0);
                    }

                    // NPM update fails
                    if (str_contains($command, 'update')) {
                        return Process::result('', 'npm error', 1);
                    }

                    return Process::result('', '', 0);
                },
            ]);

            // ACT
            $this->runMise()
                ->assertFailed();
        });

        it('handles process with stderr but success exit code', function (): void {
            // ARRANGE
            Process::fake([
                '*' => Process::result('success output', 'warning messages', 0),
            ]);

            // ACT
            $this->runMise()
                ->assertSuccessful();

            // ASSERT
            $this->assertCommandRan('composer update');
        });
    });
});
