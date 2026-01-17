<?php

declare(strict_types=1);

namespace LaravelMise\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\Command;

/**
 * Base command with styled output helpers.
 *
 * Provides DeployerPHP-style console output with line prefixes,
 * headings, success/error indicators, and formatted lists.
 */
abstract class BaseCommand extends Command
{
    private const string PACKAGE_NAME = 'loadinglucian/laravel-mise';

    // ----
    // Banner
    // ----

    /**
     * Display the application banner with name and version.
     */
    protected function banner(): void
    {
        $version = $this->getPackageVersion();

        $this->line('');
        $this->line('<fg=cyan;options=bold>▒ Laravel</><fg=blue;options=bold>Mise</> '.$version.' <fg=magenta>•</> Everything ready to start cooking!');
        $this->line('<fg=cyan;options=bold>▒ ━━━━━━━━━━━━</><fg=blue>━━━━━━━━━━━━</><fg=bright-blue>━━━━━━━━━━━━</><fg=magenta>━━━━━━━━━━━━</><fg=gray>━━━━━━━━━━━━</>');
    }

    /**
     * Get the package version from Composer.
     */
    private function getPackageVersion(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        try {
            return InstalledVersions::getPrettyVersion(self::PACKAGE_NAME) ?? 'dev';
        } catch (\OutOfBoundsException) {
            return 'dev';
        }
    }

    // ----
    // Core Output
    // ----

    /**
     * Write output lines with '▒' prefix.
     *
     * Supports color shorthand: <|gray> becomes <fg=gray>
     *
     * @param  string|iterable<string>  $lines
     */
    protected function out(string|iterable $lines): void
    {
        $writeLines = is_string($lines) ? [$lines] : $lines;

        foreach ($writeLines as $line) {
            $line = str_replace('<|', '<fg=', $line);

            // Add '▒' prefix preserving color tags
            if (preg_match('/^(\s*<[^>]+>)(.*)$/', $line, $matches)) {
                $line = $matches[1].'▒ '.$matches[2];
            } else {
                $line = '▒ '.$line;
            }

            $this->line($line);
        }
    }

    // ----
    // Structural Elements
    // ----

    /**
     * Write a horizontal rule.
     */
    protected function hr(): void
    {
        $this->out('────────────────────────────────────────────────────────────');
    }

    /**
     * Write a heading with horizontal rule.
     */
    protected function h1(string $text): void
    {
        $this->out([
            '',
            "# {$text}",
        ]);

        $this->hr();
    }

    // ----
    // Status Messages
    // ----

    /**
     * Display success message with checkmark.
     */
    protected function yay(string $message): void
    {
        $this->out("✓ {$message}");
    }

    /**
     * Display error message with X mark in red.
     */
    protected function nay(string $message): void
    {
        $this->out("<|red>✗ {$message}</>");
    }

    /**
     * Display warning message with exclamation mark.
     */
    protected function warning(string $message): void
    {
        $this->out("! {$message}");
    }
}
