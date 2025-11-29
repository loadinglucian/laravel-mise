<?php

//
// MiseCommand Feature Tests - End-to-end command behavior
// -------------------------------------------------------------------------------

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Bigpixelrocket\LaravelMise\Commands\MiseCommand;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MiseCommandHelpers;

uses(MiseCommandHelpers::class);

describe('MiseCommand Feature Tests', function (): void {
    beforeEach(function (): void {
        Process::fake();
        $this->setTestPackageConfigs();
    });

    it('has correct command signature and defaults', function (): void {
        // ARRANGE
        $command = new MiseCommand;
        $definition = $command->getDefinition();

        // ACT & ASSERT
        expect($command->getName())->toBe('laravel:mise')
            ->and($definition->getOptions())->toHaveKeys(['composer', 'npm', 'files', 'skip-composer-json', 'force'])
            ->and($definition->getOption('composer')->getDefault())->toBeFalse()
            ->and($definition->getOption('npm')->getDefault())->toBeFalse()
            ->and($definition->getOption('files')->getDefault())->toBeFalse();
    });

    it('installs all packages by default', function (): void {
        // ARRANGE & ACT
        $this->runMiseWithOptions(['--skip-composer-json' => true])
            ->expectsOutputToContain('Installing Composer Packages')
            ->expectsOutputToContain('Installing NPM Packages')
            ->expectsOutputToContain('Copying files')
            ->assertSuccessful();

        // ASSERT
        $this->assertCommandRan('composer update');
        $this->assertCommandRan('npm update');
    });

    it('respects composer-only option', function (): void {
        // ARRANGE & ACT
        $this->runMiseWithOptions(['--composer' => true, '--skip-composer-json' => true])
            ->expectsOutputToContain('Installing Composer Packages')
            ->doesntExpectOutputToContain('Installing NPM Packages')
            ->assertSuccessful();

        // ASSERT
        $this->assertCommandRan('composer require');
        $this->assertCommandDidntRun('npm install');
    });

    it('respects npm-only option', function (): void {
        // ARRANGE & ACT
        $this->runMiseWithOptions(['--npm' => true])
            ->expectsOutputToContain('Installing NPM Packages')
            ->doesntExpectOutputToContain('Installing Composer Packages')
            ->assertSuccessful();

        // ASSERT
        $this->assertCommandRan('npm install');
        $this->assertCommandDidntRun('composer require');
    });

    it('respects files-only option', function (): void {
        // ARRANGE & ACT
        $this->runMiseWithOptions(['--files' => true])
            ->expectsOutputToContain('Copying files')
            ->doesntExpectOutputToContain('Installing Composer Packages')
            ->doesntExpectOutputToContain('Installing NPM Packages')
            ->assertSuccessful();

        // ASSERT
        Process::assertNothingRan();
    });

    //
    // Composer.json Management (File Boundaries)
    // -------------------------------------------------------------------------------

    describe('composer.json management', function (): void {
        it('adds scripts to composer.json when no scripts section exists', function (): void {
            // ARRANGE
            $this->mockComposerJson(['name' => 'test/project']);
            $this->mockComposerJsonWrite();

            // ACT
            $this->runMiseWithOptions(['--composer' => true])
                ->expectsOutputToContain('Adding barryvdh/laravel-ide-helper configuration to composer.json (scripts)...')
                ->expectsOutputToContain('Added post-update-cmd scripts to composer.json')
                ->assertSuccessful();

            // ASSERT
            File::shouldHaveReceived('put')->with(
                base_path('composer.json'),
                \Mockery::on(function ($content) {
                    $data = json_decode($content, true);
                    $actualScripts = $data['scripts']['post-update-cmd'] ?? [];

                    return in_array('Illuminate\\Foundation\\ComposerScripts::postUpdate', $actualScripts) &&
                           in_array('@php artisan ide-helper:generate', $actualScripts) &&
                           in_array('@php artisan ide-helper:meta', $actualScripts);
                })
            );
        });

        it('merges scripts with existing post-update-cmd array', function (): void {
            // ARRANGE
            $this->mockComposerJson([
                'name' => 'test/project',
                'scripts' => ['post-update-cmd' => ['echo "existing"']],
            ]);
            $this->mockComposerJsonWrite();

            // ACT
            $this->runMiseWithOptions(['--composer' => true])
                ->expectsOutputToContain('Adding barryvdh/laravel-ide-helper configuration to composer.json (scripts)...')
                ->expectsOutputToContain('Added 3 new script(s) to post-update-cmd')
                ->assertSuccessful();

            // ASSERT
            File::shouldHaveReceived('put')->with(
                base_path('composer.json'),
                \Mockery::on(function ($content) {
                    $data = json_decode($content, true);
                    $actualScripts = $data['scripts']['post-update-cmd'] ?? [];

                    return count($actualScripts) === 4 &&
                           in_array('echo "existing"', $actualScripts) &&
                           in_array('Illuminate\\Foundation\\ComposerScripts::postUpdate', $actualScripts);
                })
            );
        });

        it('skips duplicate scripts when they already exist', function (): void {
            // ARRANGE
            $this->mockComposerJson([
                'name' => 'test/project',
                'scripts' => [
                    'post-update-cmd' => [
                        'Illuminate\\Foundation\\ComposerScripts::postUpdate',
                        '@php artisan ide-helper:generate',
                        '@php artisan ide-helper:meta',
                    ],
                ],
            ]);

            // ACT
            $this->runMiseWithOptions(['--composer' => true])
                ->expectsOutputToContain('Adding barryvdh/laravel-ide-helper configuration to composer.json (scripts)...')
                ->expectsOutputToContain('All required scripts already exist in post-update-cmd')
                ->assertSuccessful();
        });

        it('handles composer.json read/write errors gracefully', function (string $scenario, string $expectedError): void {
            // ARRANGE
            if ($scenario === 'missing_file') {
                $this->mockComposerJson([], false);
            } elseif ($scenario === 'invalid_json') {
                File::shouldReceive('exists')->with(base_path('composer.json'))->andReturn(true);
                File::shouldReceive('get')->with(base_path('composer.json'))->andReturn('invalid json');
            } elseif ($scenario === 'write_failure') {
                $this->mockComposerJson(['name' => 'test/project']);
                File::shouldReceive('put')
                    ->with(base_path('composer.json'), \Mockery::any())
                    ->andThrow(new \Exception('Write failed'));
            }

            // ACT
            $this->runMiseWithOptions(['--composer' => true])
                ->expectsOutputToContain($expectedError)
                ->assertSuccessful(); // Command continues despite errors
        })->with([
            'missing composer.json file' => ['missing_file', 'composer.json not found'],
            'invalid JSON content' => ['invalid_json', 'Invalid JSON in composer.json'],
            'write failure' => ['write_failure', 'Failed to update composer.json'],
        ]);
    });

    //
    // File Operations (File System Boundaries)
    // -------------------------------------------------------------------------------

    describe('file operations', function (): void {
        beforeEach(function (): void {
            Storage::fake('local');
        });

        it('copies files with proper force behavior', function (bool $useForce, string $expectedBehavior): void {
            // ARRANGE
            Storage::put('test-config.txt', 'original content');
            Storage::put('test-config.txt', 'modified content');

            $options = ['--files' => true];
            if ($useForce) {
                $options['--force'] = true;
            }

            // ACT
            $this->runMiseWithOptions($options)
                ->expectsOutputToContain($expectedBehavior)
                ->assertSuccessful();

            // ASSERT
            // Logic is verified by expectsOutputToContain above
        })->with([
            'without force - skips existing files' => [false, 'skip'],
            'with force - overwrites existing files' => [true, 'override'],
        ]);

        it('handles file copying errors gracefully', function (): void {
            // ARRANGE
            File::shouldReceive('exists')->andReturn(false);

            // ACT
            $this->runMiseWithOptions(['--files' => true])
                ->expectsOutputToContain('Copying files')
                ->assertSuccessful();

            // ASSERT
            Process::assertNothingRan();
        });
    });

    //
    // Error Handling (Process/System Failures)
    // -------------------------------------------------------------------------------

    describe('error handling', function (): void {
        it('handles composer installation failures', function (): void {
            // ARRANGE
            Process::fake(['*' => Process::result('', 'Package not found', 1)]);

            // ACT
            $this->runMiseWithOptions(['--composer' => true, '--skip-composer-json' => true])
                ->expectsOutputToContain('Installing Composer Packages')
                ->assertFailed();

            // ASSERT
            $this->assertCommandRan('composer update');
        });

        it('handles npm installation failures', function (): void {
            // ARRANGE
            Process::fake(['*' => Process::result('', 'npm package not found', 1)]);

            // ACT
            $this->runMiseWithOptions(['--npm' => true])
                ->expectsOutputToContain('Installing NPM Packages')
                ->assertFailed();

            // ASSERT
            $this->assertCommandRan('npm update');
        });

        it('continues on post-payload command failures', function (): void {
            // ARRANGE
            Process::fake([
                '*' => function (PendingProcess $process) {
                    $command = $this->extractCommand($process);

                    if (str_contains($command, 'vendor/bin/pint') ||
                        str_contains($command, 'vendor/bin/phpstan')) {
                        return Process::result('', 'Code issues found', 1);
                    }

                    return Process::result('', '', 0);
                },
            ]);

            // ACT
            $this->runMiseWithOptions(['--composer' => true, '--skip-composer-json' => true, '--force' => true])
                ->expectsOutputToContain('Post-payload command failed but continuing...')
                ->assertSuccessful();

            // ASSERT
            $this->assertCommandRan('composer require');
            $this->assertCommandRan('vendor/bin/pint --repair');
        });

        it('handles process with stderr output', function (): void {
            // ARRANGE
            Process::fake([
                '*' => Process::result('success output', 'warning messages', 0),
            ]);

            // ACT
            $this->runMiseWithOptions(['--composer' => true, '--skip-composer-json' => true, '--force' => true])
                ->assertSuccessful();

            // ASSERT
            $this->assertCommandRan('composer update');
        });
    });
});
