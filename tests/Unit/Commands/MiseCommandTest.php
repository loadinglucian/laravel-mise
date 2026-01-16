<?php

declare(strict_types=1);

use LaravelMise\Commands\MiseCommand;

describe('MiseCommand Unit Tests', function (): void {
    describe('command interface', function (): void {
        it('has correct name and description', function (): void {
            // ARRANGE
            $command = app()->make(MiseCommand::class);

            // ACT & ASSERT
            expect($command->getName())->toBe('laravel:mise')
                ->and($command->getDescription())->toBe('Laravel Mise en Place');
        });

        it('has only force option for simplified interface', function (): void {
            // ARRANGE
            $command = app()->make(MiseCommand::class);
            $definition = $command->getDefinition();
            $defaultOptions = ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'env'];

            // ACT
            $options = $definition->getOptions();
            $customOptions = array_filter(
                $options,
                static fn ($key) => ! in_array($key, $defaultOptions, true),
                ARRAY_FILTER_USE_KEY,
            );

            // ASSERT
            expect($customOptions)->toHaveCount(1)
                ->and($customOptions)->toHaveKey('force')
                ->and($definition->getOption('force')->getDefault())->toBeFalse();
        });
    });
});
