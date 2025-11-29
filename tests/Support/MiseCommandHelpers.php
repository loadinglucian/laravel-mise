<?php

//
// Shared Mise en Place Command Test Helpers
// ----

declare(strict_types=1);

namespace Tests\Support;

use Bigpixelrocket\LaravelMise\Commands\MiseCommand;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

use function Pest\Laravel\artisan;

trait MiseCommandHelpers
{
    //
    // Command Execution
    // ----

    /**
     * Run mise command with options
     */
    protected function runMiseWithOptions(array $options): \Illuminate\Testing\PendingCommand
    {
        return artisan(MiseCommand::class, $options);
    }

    //
    // Process Assertions
    // ----

    /**
     * Extract command string from PendingProcess for assertions
     */
    protected function extractCommand(PendingProcess $process): string
    {
        return is_array($process->command) ? implode(' ', $process->command) : $process->command;
    }

    /**
     * Assert that a command containing the specified fragment was run
     */
    protected function assertCommandRan(string $commandFragment): void
    {
        Process::assertRan(function (PendingProcess $process) use ($commandFragment) {
            $command = $this->extractCommand($process);

            return str_contains($command, $commandFragment);
        });
    }

    /**
     * Assert that a command containing the specified fragment was NOT run
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
     * Set test package configurations
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

        Config::set('laravel-mise.npm-packages', [
            'dependencies' => ['tailwindcss', '@tailwindcss/vite'],
            'devDependencies' => ['prettier', 'prettier-plugin-blade'],
        ]);
    }

    //
    // Mocking Helpers (for Feature Tests)
    // ----

    /**
     * Mock composer.json file with content
     */
    protected function mockComposerJson(array $content, bool $exists = true): void
    {
        File::shouldReceive('exists')
            ->with(base_path('composer.json'))
            ->andReturn($exists);

        if ($exists) {
            File::shouldReceive('get')
                ->with(base_path('composer.json'))
                ->andReturn(json_encode($content, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Mock composer.json write operations
     */
    protected function mockComposerJsonWrite(): void
    {
        File::shouldReceive('put')
            ->with(base_path('composer.json'), \Mockery::any())
            ->andReturn(true);
    }
}
