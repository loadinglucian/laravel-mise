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

        if ($tty && 'Windows' !== PHP_OS_FAMILY && ! app()->runningUnitTests()) {
            $process->tty();
        }

        return $process->run();
    }

    /**
     * Run command with output callback, stripping external '▒ ' prefixes.
     *
     * Preserves ANSI color codes and formatting while removing the prefix.
     *
     * @param  array<string>  $command
     * @param  callable(string): void  $outputCallback
     */
    public function runWithOutput(array $command, callable $outputCallback): ProcessResult
    {
        return Process::command($command)->run(output: function (string $type, string $output) use ($outputCallback): void {
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                $outputCallback($this->stripOutputPrefix($line));
            }
        });
    }

    //
    // Output Processing
    // ----

    /**
     * Strip '▒ ' prefix while preserving ANSI color codes.
     *
     * Handles patterns like:
     * - "▒ text" → "text"
     * - "\e[36m▒ \e[0mtext" → "\e[36m\e[0mtext" (preserves colors)
     * - "\e[36m▒ text\e[0m" → "\e[36mtext\e[0m" (preserves colors)
     */
    private function stripOutputPrefix(string $line): string
    {
        // Match: optional leading ANSI codes, then ▒ followed by optional space, then optional ANSI codes
        // ANSI escape: \e[ or \033[ followed by params and 'm'
        $pattern = '/^((?:\e\[[0-9;]*m)*)▒ ?((?:\e\[[0-9;]*m)*)/';

        return preg_replace($pattern, '$1$2', $line) ?? $line;
    }
}
