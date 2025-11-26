<?php

declare(strict_types=1);

use Bigpixelrocket\LaravelOmakase\Commands\DbMigrateCommand;

describe('DbMigrateCommand Unit Tests', function (): void {
    describe('command interface', function (): void {
        it('has correct signature and description', function (): void {
            // ARRANGE
            $command = new DbMigrateCommand;

            // ACT & ASSERT
            expect($command->getName())->toBe('db:migrate')
                ->and($command->getDescription())->toBe('Alias for the migrate command - Run the database migrations');
        });

        it('supports all migrate command options', function (): void {
            // ARRANGE
            $command = new DbMigrateCommand;
            $defaultOptions = ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'env'];
            $expectedOptions = [
                'database', 'force', 'path', 'realpath', 'schema-path',
                'pretend', 'seed', 'seeder', 'step', 'graceful', 'isolated',
            ];

            // ACT
            $definition = $command->getDefinition();
            $options = $definition->getOptions();
            $customOptions = array_filter($options, static fn ($key) => ! in_array($key, $defaultOptions), ARRAY_FILTER_USE_KEY);

            // ASSERT
            foreach ($expectedOptions as $option) {
                expect($customOptions)->toHaveKey($option);
            }

            expect($customOptions)->toHaveCount(count($expectedOptions));
        });
    });
});
