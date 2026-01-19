<?php

declare(strict_types=1);

use LaravelMise\Commands\MiseCommand;

//
// Test double that exposes protected extractOptions method
// ----

class TestableMiseCommand extends MiseCommand
{
    /**
     * Public wrapper to test extractOptions().
     *
     * @param  array<string, mixed>  $packageConfig
     * @return array<string>
     */
    public function testExtractOptions(array $packageConfig): array
    {
        return $this->extractOptions($packageConfig);
    }
}

describe('MiseCommand Unit Tests', function (): void {
    describe('command interface', function (): void {
        it('has correct name and description', function (): void {
            // ARRANGE
            $command = app()->make(MiseCommand::class);

            // ACT & ASSERT
            expect($command->getName())->toBe('mise')
                ->and($command->getDescription())->toBe('Laravel Mise en Place');
        });

        it('has only yes option for simplified interface', function (): void {
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

            // ASSERT - only --yes option should be custom
            expect($customOptions)->toHaveCount(1)
                ->and(array_key_exists('yes', $customOptions))->toBeTrue();
        });
    });

    //
    // extractOptions() tests
    // ----

    describe('extractOptions()', function (): void {
        beforeEach(function (): void {
            $this->command = app()->make(TestableMiseCommand::class);
        });

        it('returns empty array when no command_options key exists', function (): void {
            // ARRANGE
            $config = ['commands' => [['artisan', 'migrate']]];

            // ACT
            $result = $this->command->testExtractOptions($config);

            // ASSERT
            expect($result)->toBe([]);
        });

        it('returns empty array when command_options is not an array', function (): void {
            // ARRANGE
            $config = ['command_options' => 'invalid'];

            // ACT
            $result = $this->command->testExtractOptions($config);

            // ASSERT
            expect($result)->toBe([]);
        });

        it('returns array of string options', function (): void {
            // ARRANGE
            $config = ['command_options' => ['destination', 'force']];

            // ACT
            $result = $this->command->testExtractOptions($config);

            // ASSERT
            expect($result)->toBe(['destination', 'force']);
        });

        it('filters out non-string values', function (): void {
            // ARRANGE
            $config = ['command_options' => ['destination', 123, null, 'force', ['nested']]];

            // ACT
            $result = $this->command->testExtractOptions($config);

            // ASSERT
            expect($result)->toBe(['destination', 'force']);
        });

        it('returns empty array for empty command_options', function (): void {
            // ARRANGE
            $config = ['command_options' => []];

            // ACT
            $result = $this->command->testExtractOptions($config);

            // ASSERT
            expect($result)->toBe([]);
        });
    });
});
