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
}
