<?php

//
// Shared Mise en Place Command Test Helpers
// ----

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Process;
use Illuminate\Testing\PendingCommand;
use LaravelMise\Commands\MiseCommand;

use function Pest\Laravel\artisan;

trait MiseCommandHelpers
{
    //
    // Command Execution
    // ----

    /**
     * Run mise command with optional options.
     *
     * @param  array<string, mixed>  $options
     */
    protected function runMise(array $options = []): PendingCommand
    {
        $options['--yes'] ??= true;

        return artisan(MiseCommand::class, $options);
    }

    //
    // Process Assertions
    // ----

    /**
     * Extract command string from PendingProcess for assertions.
     */
    protected function extractCommand(PendingProcess $process): string
    {
        return is_array($process->command) ? implode(' ', $process->command) : $process->command;
    }

    /**
     * Assert that a command containing the specified fragment was run.
     */
    protected function assertCommandRan(string $commandFragment): void
    {
        Process::assertRan(function (PendingProcess $process) use ($commandFragment) {
            $command = $this->extractCommand($process);

            return str_contains($command, $commandFragment);
        });
    }

    /**
     * Assert that a command containing the specified fragment was NOT run.
     */
    protected function assertCommandDidntRun(string $commandFragment): void
    {
        Process::assertDidntRun(function (PendingProcess $process) use ($commandFragment) {
            $command = $this->extractCommand($process);

            return str_contains($command, $commandFragment);
        });
    }

    //
    // Test Data Setup
    // ----

    /**
     * Set test package configurations.
     */
    protected function setTestPackageConfigs(): void
    {
        Config::set('laravel-mise.composer-packages', [
            'require' => [
                'livewire/livewire' => [
                    'commands' => [
                        ['php', 'artisan', 'livewire:publish', '--config'],
                    ],
                ],
                'livewire/flux' => [
                    'post_payload_commands' => [
                        ['php', 'artisan', 'flux:activate'],
                    ],
                ],
            ],
            'require-dev' => [
                'barryvdh/laravel-ide-helper' => [
                    'composer' => [
                        'scripts' => [
                            'post-update-cmd' => [
                                'Illuminate\\Foundation\\ComposerScripts::postUpdate',
                                '@php artisan ide-helper:generate',
                                '@php artisan ide-helper:meta',
                            ],
                        ],
                    ],
                    'commands' => [
                        ['php', 'artisan', 'ide-helper:generate'],
                        ['php', 'artisan', 'ide-helper:meta'],
                    ],
                ],
                'test/with-command-options' => [
                    'commands' => [
                        ['vendor/bin/test-scaffold', 'init'],
                    ],
                    'command_options' => ['destination', 'force'],
                ],
                'rector/rector' => [
                    'post_payload_commands' => [
                        ['vendor/bin/rector'],
                    ],
                ],
                'laravel/pint' => [
                    'post_payload_commands' => [
                        ['vendor/bin/pint', '--repair'],
                    ],
                ],
                'roave/security-advisories:dev-latest',
            ],
        ]);

        Config::set('laravel-mise.node-packages', [
            'dependencies' => ['tailwindcss', '@tailwindcss/vite'],
            'devDependencies' => ['prettier', 'prettier-plugin-blade'],
        ]);
    }
}
