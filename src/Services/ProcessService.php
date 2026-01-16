<?php

declare(strict_types=1);

namespace LaravelMise\Services;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process;

//
// Process Service - Shell Command Execution
// ----
//
// Executes shell commands via Laravel's Process facade.
// Returns structured results for callers to handle output formatting.

readonly class ProcessService
{
    //
    // Command Execution
    // ----

    /**
     * Execute a single command.
     *
     * @param  array<string>  $command
     */
    public function run(array $command, bool $tty = true): ProcessResult
    {
        $process = Process::command($command);

        if ($tty && PHP_OS_FAMILY !== 'Windows' && ! app()->runningUnitTests()) {
            $process->tty();
        }

        return $process->run();
    }

    /**
     * Execute multiple commands in sequence.
     * Stops on first failure.
     *
     * @param  array<array<string>>  $commands
     * @return array{success: bool, results: array<ProcessResult>}
     */
    public function runBatch(array $commands): array
    {
        $results = [];

        foreach ($commands as $command) {
            $result = $this->run($command);
            $results[] = $result;

            if (! $result->successful()) {
                return ['success' => false, 'results' => $results];
            }
        }

        return ['success' => true, 'results' => $results];
    }
}
