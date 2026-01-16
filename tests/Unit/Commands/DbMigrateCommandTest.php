<?php

declare(strict_types=1);

use LaravelMise\Commands\DbMigrateCommand;

describe('DbMigrateCommand Unit Tests', function (): void {
    describe('command interface', function (): void {
        it('has correct signature and description', function (): void {
            // ARRANGE
            $command = new DbMigrateCommand;

            // ACT & ASSERT
            expect($command->getName())->toBe('db:migrate')
                ->and($command->getDescription())->toBe('Alias for the migrate command - Run the database migrations');
        });

        it('has minimal signature for process forwarding', function (): void {
            // ARRANGE
            $command = new DbMigrateCommand;
            $defaultOptions = ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'env'];

            // ACT
            $definition = $command->getDefinition();
            $options = $definition->getOptions();
            $customOptions = array_filter(
                $options,
                static fn ($key) => ! in_array($key, $defaultOptions, true),
                ARRAY_FILTER_USE_KEY,
            );

            // ASSERT
            // Command uses Process forwarding, so no custom options defined
            expect($customOptions)->toHaveCount(0);
        });
    });
});
