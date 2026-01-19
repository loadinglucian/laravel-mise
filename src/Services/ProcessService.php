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
// Uses TTY mode when available for native terminal output,
// falls back to output callback in non-TTY environments.

readonly class ProcessService
{
    /**
     * Execute a command with TTY support and optional output callback.
     *
     * Uses TTY mode when available (native terminal output with colors/progress).
     * Falls back to output callback in non-TTY environments (CI, tests).
     *
     * @param  array<string>  $command
     * @param  (callable(string): void)|null  $outputCallback
     */
    public function run(array $command, ?callable $outputCallback = null): ProcessResult
    {
        $process = Process::command($command);

        // Use TTY when: no callback needed AND environment supports it
        if ($this->shouldUseTty($outputCallback)) {
            return $process->tty()->run();
        }

        // Callback mode for non-TTY environments or when caller needs output processing
        return $process->run(output: $outputCallback === null
            ? null
            : fn (string $_type, string $output) => $outputCallback($output)
        );
    }

    /**
     * Determine if TTY mode should be used.
     *
     * TTY provides native terminal output (colors, progress bars).
     * Extracted to allow testing of TTY code path.
     *
     * @param  (callable(string): void)|null  $outputCallback
     */
    protected function shouldUseTty(?callable $outputCallback): bool
    {
        return $outputCallback === null
            && 'Windows' !== PHP_OS_FAMILY
            && ! app()->runningUnitTests();
    }
}
