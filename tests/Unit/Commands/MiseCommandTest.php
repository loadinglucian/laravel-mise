<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use Bigpixelrocket\LaravelMise\Commands\MiseCommand;

describe('MiseCommand Unit Tests', function (): void {
    describe('command interface', function (): void {
        it('has correct signature and options', function (): void {
            $command = new MiseCommand;
            $definition = $command->getDefinition();

            expect($command->getName())->toBe('laravel:mise')
                ->and($definition->getOptions())->toHaveKeys([
                    'composer', 'npm', 'files', 'skip-composer-json', 'force',
                ]);
        });
    });

    describe('option defaults', function (): void {
        it('defaults all flags to false', function (): void {
            $command = new MiseCommand;
            $definition = $command->getDefinition();

            expect($definition->getOption('composer')->getDefault())->toBeFalse()
                ->and($definition->getOption('npm')->getDefault())->toBeFalse()
                ->and($definition->getOption('files')->getDefault())->toBeFalse()
                ->and($definition->getOption('skip-composer-json')->getDefault())->toBeFalse()
                ->and($definition->getOption('force')->getDefault())->toBeFalse();
        });
    });
});
