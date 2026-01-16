<?php

declare(strict_types=1);

namespace LaravelMise\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Process as SymfonyProcess;

class DbMigrateCommand extends Command
{
    protected $signature = 'db:migrate';

    protected $description = 'Alias for the migrate command - Run the database migrations';

    public function handle(): int
    {
        //
        // Forward to Migrate Command
        // ----

        /** @var array<int, string> $argv */
        $argv = $_SERVER['argv'];

        // Forward all arguments to the migrate command
        $args = array_slice($argv, 2);

        $process = Process::forever();

        if (SymfonyProcess::isTtySupported()) {
            $process = $process->tty();
        }

        $result = $process->run([PHP_BINARY, 'artisan', 'migrate', ...$args]);

        return $result->exitCode() ?? 1;
    }
}
