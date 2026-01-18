<?php

//
// MiseCommand Feature Tests - End-to-end command behavior
// ----

declare(strict_types=1);

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Config;
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
        expect($command->getName())->toBe('mise')
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
        it('fails when composer-packages config is not an array', function (): void {
            // ARRANGE
            Config::set('laravel-mise.composer-packages', 'invalid');

            // ACT
            $this->runMise()
                ->expectsOutputToContain('Invalid composer packages configuration')
                ->assertFailed();
        });

        it('fails when node-packages config is not an array', function (): void {
            // ARRANGE
            Config::set('laravel-mise.node-packages', 'invalid');

            // ACT
            $this->runMise()
                ->expectsOutputToContain('Invalid node packages configuration')
                ->assertFailed();
        });

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

    //
    // Environment Configuration
    // ----

    describe('environment configuration', function (): void {
        it('displays environment files section header', function (): void {
            // ACT & ASSERT - verify section appears in command output
            $this->runMise()
                ->expectsOutputToContain('Updating Environment Files')
                ->assertSuccessful();
        });

        it('updates env files when present', function (): void {
            // ACT & ASSERT - testbench skeleton includes .env.example and .env.testing
            $this->runMise()
                ->expectsOutputToContain('Updated')
                ->assertSuccessful();
        });
    });

    //
    // Command Options Injection
    // ----

    describe('command options injection', function (): void {
        it('injects destination option when configured', function (): void {
            // ARRANGE & ACT
            $this->runMise()
                ->assertSuccessful();

            // ASSERT
            $this->assertCommandRan('vendor/bin/test-scaffold init --destination');
        });

        it('injects force option when configured and --force flag passed', function (): void {
            // ARRANGE & ACT
            $this->runMise(['--force' => true])
                ->assertSuccessful();

            // ASSERT
            $this->assertCommandRan('vendor/bin/test-scaffold init --destination');
            $this->assertCommandRan('--force');
        });

        it('does not inject force option when --force flag not passed', function (): void {
            // ARRANGE & ACT
            $this->runMise()
                ->assertSuccessful();

            // ASSERT - destination should be present, but not force
            $this->assertCommandRan('vendor/bin/test-scaffold init --destination');
            Process::assertDidntRun(function (PendingProcess $process) {
                $command = $this->extractCommand($process);

                // Check specifically for test-scaffold with --force
                return str_contains($command, 'test-scaffold') && str_contains($command, '--force');
            });
        });

        it('does not inject command options into unrelated post-payload commands', function (): void {
            // ARRANGE & ACT
            // test/with-command-options has command_options but NO post_payload_commands
            // rector/rector has post_payload_commands but NO command_options
            // Options from test/with-command-options should NOT leak to rector
            $this->runMise()
                ->assertSuccessful();

            // ASSERT - rector's post-payload command should NOT have --destination
            // Use vendor/bin/rector to match the post-payload command, not the package name
            Process::assertDidntRun(function (PendingProcess $process) {
                $command = $this->extractCommand($process);

                return str_contains($command, 'vendor/bin/rector') && str_contains($command, '--destination');
            });

            // ASSERT - pint's post-payload command should NOT have --destination either
            // Use vendor/bin/pint to match the post-payload command, not the package name
            Process::assertDidntRun(function (PendingProcess $process) {
                $command = $this->extractCommand($process);

                return str_contains($command, 'vendor/bin/pint') && str_contains($command, '--destination');
            });
        });
    });
});
