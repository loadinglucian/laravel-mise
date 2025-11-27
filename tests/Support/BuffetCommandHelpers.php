<?php

//
// Shared Buffet Command Test Helpers
// -------------------------------------------------------------------------------

declare(strict_types=1);

namespace Tests\Support;

use Bigpixelrocket\LaravelBuffet\Commands\BuffetCommand;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

use function Pest\Laravel\artisan;

trait BuffetCommandHelpers
{
    //
    // Command Execution
    // -------------------------------------------------------------------------------

    /**
     * Run buffet command with options
     */
    protected function runBuffetWithOptions(array $options): \Illuminate\Testing\PendingCommand
    {
        return artisan(BuffetCommand::class, $options);
    }

    //
    // Process Assertions
    // -------------------------------------------------------------------------------

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
    // -------------------------------------------------------------------------------

    /**
     * Set test package configurations
     */
    protected function setTestPackageConfigs(): void
    {
        Config::set('laravel-buffet.composer-packages', [
            'require' => [
                'livewire/livewire' => [
                    'commands' => [
                        ['php', 'artisan', 'livewire:publish', '--config'],
                    ],
                ],
                'livewire/flux' => [
                    'post_dist_commands' => [
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
                    'post_dist_commands' => [
                        ['vendor/bin/rector'],
                    ],
                ],
                'laravel/pint' => [
                    'post_dist_commands' => [
                        ['vendor/bin/pint', '--repair'],
                    ],
                ],
                'roave/security-advisories:dev-latest',
            ],
        ]);

        Config::set('laravel-buffet.npm-packages', [
            'dependencies' => ['tailwindcss', '@tailwindcss/vite'],
            'devDependencies' => ['prettier', 'prettier-plugin-blade'],
        ]);
    }

    //
    // Mocking Helpers (for Integration Tests)
    // -------------------------------------------------------------------------------

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
