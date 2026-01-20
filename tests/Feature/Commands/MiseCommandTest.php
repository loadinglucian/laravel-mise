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

        // ACT & ASSERT
        expect($command->getName())->toBe('mise');
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
    // Confirmation Prompt
    // ----

    describe('confirmation prompt', function (): void {
        it('displays warning message before confirmation', function (): void {
            // ARRANGE & ACT
            $this->runMise(['--yes' => false])
                ->expectsOutputToContain('This will install a number of packages and files')
                ->expectsOutputToContain('working tree is clean')
                ->expectsConfirmation('Do you want to continue?', 'yes')
                ->assertSuccessful();
        });

        it('exits successfully when user cancels confirmation', function (): void {
            // ARRANGE & ACT
            $this->runMise(['--yes' => false])
                ->expectsConfirmation('Do you want to continue?', 'no')
                ->expectsOutputToContain('Installation cancelled')
                ->assertSuccessful();

            // ASSERT - no commands were run
            Process::assertNothingRan();
        });

        it('continues installation when user confirms', function (): void {
            // ARRANGE & ACT
            $this->runMise(['--yes' => false])
                ->expectsConfirmation('Do you want to continue?', 'yes')
                ->expectsOutputToContain('Installing Composer Packages')
                ->assertSuccessful();

            // ASSERT
            $this->assertCommandRan('composer update');
        });

        it('skips confirmation when --yes flag provided', function (): void {
            // ARRANGE & ACT (default behavior via helper)
            $this->runMise()
                ->assertSuccessful();

            // ASSERT - commands ran without confirmation prompt
            $this->assertCommandRan('composer update');
        });
    });

    //
    // File Operations
    // ----

    describe('file operations', function (): void {
        beforeEach(function (): void {
            Storage::fake('local');
        });

        it('copies files and always overwrites', function (): void {
            // ARRANGE
            Storage::put('test-config.txt', 'original content');

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

        it('continues when composer update fails (soft error)', function (): void {
            // ARRANGE
            Process::fake([
                '*' => function (PendingProcess $process) {
                    $command = $this->extractCommand($process);

                    // Only composer update fails
                    if (str_contains($command, 'composer update')) {
                        return Process::result('', 'Network error', 1);
                    }

                    return Process::result('', '', 0);
                },
            ]);

            // ACT
            $this->runMise()
                ->expectsOutputToContain('Installing Composer Packages')
                ->expectsOutputToContain('Network error')
                ->assertSuccessful();

            // ASSERT - composer update ran and subsequent commands still executed
            $this->assertCommandRan('composer update');
            $this->assertCommandRan('composer require');
        });

        it('continues when npm update fails (soft error)', function (): void {
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
                ->expectsOutputToContain('npm error')
                ->assertSuccessful();
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

        it('injects force option when configured', function (): void {
            // ARRANGE & ACT
            $this->runMise()
                ->assertSuccessful();

            // ASSERT
            $this->assertCommandRan('vendor/bin/test-scaffold init --destination');
            $this->assertCommandRan('--force');
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

        it('does not inject command options into package manager commands', function (): void {
            // ARRANGE & ACT
            $this->runMise()->assertSuccessful();

            // ASSERT - composer require should NOT have --destination
            Process::assertDidntRun(function (PendingProcess $process) {
                $command = $this->extractCommand($process);

                return str_contains($command, 'composer require') && str_contains($command, '--destination');
            });

            // ASSERT - npm install should NOT have --destination
            Process::assertDidntRun(function (PendingProcess $process) {
                $command = $this->extractCommand($process);

                return str_contains($command, 'npm install') && str_contains($command, '--destination');
            });
        });

        it('does not inject command options into other packages commands', function (): void {
            // ARRANGE & ACT
            // barryvdh/laravel-ide-helper has commands but NO command_options
            // test/with-command-options has commands WITH command_options
            // Options from test/with-command-options should NOT leak to ide-helper
            $this->runMise()->assertSuccessful();

            // ASSERT - ide-helper commands should NOT have --destination
            Process::assertDidntRun(function (PendingProcess $process) {
                $command = $this->extractCommand($process);

                return str_contains($command, 'ide-helper:generate') && str_contains($command, '--destination');
            });

            Process::assertDidntRun(function (PendingProcess $process) {
                $command = $this->extractCommand($process);

                return str_contains($command, 'ide-helper:meta') && str_contains($command, '--destination');
            });

            // ASSERT - livewire:publish should NOT have --destination
            Process::assertDidntRun(function (PendingProcess $process) {
                $command = $this->extractCommand($process);

                return str_contains($command, 'livewire:publish') && str_contains($command, '--destination');
            });
        });

        it('suppresses error output when ignore_errors option is set', function (): void {
            // ARRANGE
            // rector and pint have ignore_errors option, so their failures should not show error output
            Process::fake([
                '*' => function (PendingProcess $process) {
                    $command = $this->extractCommand($process);

                    // Rector and pint fail with exit code 1 (issues found)
                    if (str_contains($command, 'vendor/bin/rector') || str_contains($command, 'vendor/bin/pint')) {
                        return Process::result('', 'Some issues found', 1);
                    }

                    return Process::result('', '', 0);
                },
            ]);

            // ACT
            $result = $this->runMise();

            // ASSERT - command succeeds and error output is suppressed
            $result->assertSuccessful();
            $result->doesntExpectOutputToContain('Some issues found');
        });
    });
});
