<?php

//
// OmakaseCommand Feature Tests - End-to-end command behavior
// -------------------------------------------------------------------------------

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Bigpixelrocket\LaravelOmakase\Commands\OmakaseCommand;
use Illuminate\Support\Facades\Process;
use Tests\Support\OmakaseCommandHelpers;

uses(OmakaseCommandHelpers::class);

describe('OmakaseCommand Feature Tests', function (): void {
    beforeEach(function (): void {
        Process::fake();
        $this->setTestPackageConfigs();
    });

    it('has correct command signature and defaults', function (): void {
        // ARRANGE
        $command = new OmakaseCommand;
        $definition = $command->getDefinition();

        // ACT & ASSERT
        expect($command->getName())->toBe('laravel:omakase')
            ->and($definition->getOptions())->toHaveKeys(['composer', 'npm', 'files', 'skip-composer-json', 'force'])
            ->and($definition->getOption('composer')->getDefault())->toBeFalse()
            ->and($definition->getOption('npm')->getDefault())->toBeFalse()
            ->and($definition->getOption('files')->getDefault())->toBeFalse();
    });

    it('installs all packages by default', function (): void {
        // ARRANGE & ACT
        $this->runOmakaseWithOptions(['--skip-composer-json' => true])
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
        $this->runOmakaseWithOptions(['--composer' => true, '--skip-composer-json' => true])
            ->expectsOutputToContain('Installing Composer Packages')
            ->doesntExpectOutputToContain('Installing NPM Packages')
            ->assertSuccessful();

        // ASSERT
        $this->assertCommandRan('composer require');
        $this->assertCommandDidntRun('npm install');
    });

    it('respects npm-only option', function (): void {
        // ARRANGE & ACT
        $this->runOmakaseWithOptions(['--npm' => true])
            ->expectsOutputToContain('Installing NPM Packages')
            ->doesntExpectOutputToContain('Installing Composer Packages')
            ->assertSuccessful();

        // ASSERT
        $this->assertCommandRan('npm install');
        $this->assertCommandDidntRun('composer require');
    });

    it('respects files-only option', function (): void {
        // ARRANGE & ACT
        $this->runOmakaseWithOptions(['--files' => true])
            ->expectsOutputToContain('Copying files')
            ->doesntExpectOutputToContain('Installing Composer Packages')
            ->doesntExpectOutputToContain('Installing NPM Packages')
            ->assertSuccessful();

        // ASSERT
        Process::assertNothingRan();
    });
});

