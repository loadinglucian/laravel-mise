<?php

declare(strict_types=1);

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;

use function Pest\Laravel\artisan;

describe('DbMigrateCommand Feature Tests', function (): void {
    it('is registered in the console kernel', function (): void {
        // ARRANGE & ACT
        $commands = app()->make(\Illuminate\Contracts\Console\Kernel::class)->all();

        // ASSERT
        expect($commands)->toHaveKey('db:migrate');
    });

    describe('command forward execution', function (): void {
        it('executes migrate command via Process facade', function (): void {
            // ARRANGE
            Process::fake([
                '*' => Process::result(output: 'Nothing to migrate.'),
            ]);

            // ACT
            artisan('db:migrate')->assertSuccessful();

            // ASSERT
            Process::assertRan(function (PendingProcess $process): bool {
                $command = $process->command;

                return is_array($command)
                    && in_array('migrate', $command, true)
                    && in_array(PHP_BINARY, $command, true);
            });
        });
    });
});
