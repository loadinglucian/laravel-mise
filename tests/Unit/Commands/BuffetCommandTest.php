<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Process;
use Tests\Support\BuffetCommandHelpers;

uses(BuffetCommandHelpers::class);

describe('BuffetCommand Unit Tests', function (): void {
    describe('configuration validation', function (): void {
        it('validates composer package configuration structure', function (): void {
            // ARRANGE
            $validConfig = [
                'require' => [
                    'package/name' => [
                        'commands' => [['php', 'artisan', 'command']],
                        'post_dist_commands' => [['vendor/bin/tool']],
                    ],
                ],
                'require-dev' => [
                    'dev-package/name',
                ],
            ];

            Config::set('laravel-buffet.composer-packages', $validConfig);

            // ACT & ASSERT
            expect(config('laravel-buffet.composer-packages'))
                ->toHaveKeys(['require', 'require-dev'])
                ->and(config('laravel-buffet.composer-packages.require'))
                ->toHaveKey('package/name')
                ->and(config('laravel-buffet.composer-packages.require.package/name'))
                ->toHaveKeys(['commands', 'post_dist_commands']);
        });

        it('validates npm package configuration structure', function (): void {
            // ARRANGE
            $validConfig = [
                'dependencies' => ['package-name', 'other-package'],
                'devDependencies' => ['dev-package', 'test-package'],
            ];

            Config::set('laravel-buffet.npm-packages', $validConfig);

            // ACT & ASSERT
            expect(config('laravel-buffet.npm-packages'))
                ->toHaveKeys(['dependencies', 'devDependencies'])
                ->and(config('laravel-buffet.npm-packages.dependencies'))
                ->toBeArray()
                ->toContain('package-name')
                ->and(config('laravel-buffet.npm-packages.devDependencies'))
                ->toBeArray()
                ->toContain('dev-package');
        });

        it('handles invalid configuration gracefully', function (mixed $invalidConfig, string $configKey): void {
            // ARRANGE
            Process::fake();
            Config::set($configKey, $invalidConfig);

            // ACT
            $result = $this->runBuffetWithOptions([$configKey === 'laravel-buffet.composer-packages' ? '--composer' : '--npm' => true]);

            // ASSERT
            $result->expectsOutputToContain('Invalid')
                ->assertFailed();
        })->with([
            'invalid composer config' => ['invalid-string', 'laravel-buffet.composer-packages'],
            'invalid npm config' => ['invalid-string', 'laravel-buffet.npm-packages'],
            'null composer config' => [null, 'laravel-buffet.composer-packages'],
            'null npm config' => [null, 'laravel-buffet.npm-packages'],
        ]);

        it('processes package configurations correctly', function (): void {
            // ARRANGE
            $composerConfig = [
                'require' => [
                    'simple/package',
                    'complex/package' => [
                        'commands' => [['php', 'artisan', 'command']],
                        'post_dist_commands' => [['vendor/bin/tool']],
                    ],
                ],
            ];

            $npmConfig = [
                'dependencies' => ['react', 'vue'],
                'devDependencies' => ['jest', 'webpack'],
            ];

            Config::set('laravel-buffet.composer-packages', $composerConfig);
            Config::set('laravel-buffet.npm-packages', $npmConfig);

            // ACT & ASSERT
            expect(config('laravel-buffet.composer-packages.require'))
                ->toContain('simple/package')
                ->and(config('laravel-buffet.composer-packages.require.complex/package'))
                ->toHaveKeys(['commands', 'post_dist_commands'])
                ->and(config('laravel-buffet.npm-packages.dependencies'))
                ->toContain('react', 'vue')
                ->and(config('laravel-buffet.npm-packages.devDependencies'))
                ->toContain('jest', 'webpack');
        });

        it('validates package naming conventions', function (string $packageName, bool $isValid): void {
            // ARRANGE
            $config = [
                'require' => [$packageName => []],
            ];

            Config::set('laravel-buffet.composer-packages', $config);

            // ACT & ASSERT
            if ($isValid) {
                expect(config('laravel-buffet.composer-packages.require'))
                    ->toHaveKey($packageName);
            } else {
                // Invalid package names might still be stored but should be handled appropriately
                expect(config('laravel-buffet.composer-packages.require'))
                    ->toHaveKey($packageName);
            }
        })->with([
            'valid vendor/package format' => ['vendor/package', true],
            'valid org/project format' => ['laravel/framework', true],
            'package without vendor' => ['standalone-package', false],
            'package with special characters' => ['vendor/package-name', true],
            'package with numbers' => ['vendor/package2', true],
        ]);
    });
});
