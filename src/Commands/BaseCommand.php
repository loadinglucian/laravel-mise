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
    private const PACKAGE_NAME = 'loadinglucian/laravel-mise';

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

    /**
     * Display info message with info symbol.
     */
    protected function notice(string $message): void
    {
        $this->out("ℹ {$message}");
    }

    // ----
    // Lists
    // ----

    /**
     * Display unordered list with bullet points.
     *
     * @param  string|iterable<string>  $lines
     */
    protected function ul(string|iterable $lines): void
    {
        $writeLines = is_string($lines) ? [$lines] : $lines;
        $bulletLines = [];

        foreach ($writeLines as $line) {
            $bulletLines[] = "• {$line}";
        }

        $this->out($bulletLines);
    }

    /**
     * Display ordered list with numbers.
     *
     * @param  string|iterable<string>  $lines
     */
    protected function ol(string|iterable $lines): void
    {
        $writeLines = is_string($lines) ? [$lines] : $lines;
        $numberedLines = [];
        $counter = 1;

        foreach ($writeLines as $line) {
            $numberedLines[] = "{$counter}. {$line}";
            $counter++;
        }

        $this->out($numberedLines);
    }

    // ----
    // Formatted Output
    // ----

    /**
     * Display key-value details with aligned formatting.
     *
     * @param  array<string, mixed>  $details  Key-value pairs to display
     * @param  bool  $asList  Whether to prefix with bullet points
     */
    protected function displayDeets(array $details, bool $asList = false): void
    {
        if (empty($details)) {
            return;
        }

        // Find longest key for alignment
        $maxLength = max(array_map(strlen(...), array_keys($details)));

        foreach ($details as $key => $value) {
            $paddedKey = str_pad($key.':', $maxLength + 1);

            if (is_array($value)) {
                $this->out("{$paddedKey}");
                /** @var array<string, mixed> $value */
                $this->displayDeets($value, true);

                continue;
            }

            /** @var string|int|float|bool|null $value */
            $prefix = $asList ? '• ' : '';
            $this->out("{$prefix}{$paddedKey} <|gray>{$value}</>");
        }
    }
}
