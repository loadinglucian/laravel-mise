<?php

//
// MiseCommand Feature Tests - End-to-end command behavior
// -------------------------------------------------------------------------------

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Bigpixelrocket\LaravelMise\Commands\MiseCommand;
use Illuminate\Support\Facades\Process;
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
});
