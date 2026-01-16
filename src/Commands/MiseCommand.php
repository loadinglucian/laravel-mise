<?php

declare(strict_types=1);

namespace LaravelMise\Commands;

use LaravelMise\Enums\CopyResultEnum;
use LaravelMise\Services\ComposerJsonService;
use LaravelMise\Services\NodeDetector;
use LaravelMise\Services\PayloadService;
use LaravelMise\Services\ProcessService;

//
// Mise en Place Command - Laravel Package Installation & Configuration
// ----
//
// This command provides an opinionated selection of packages and configurations
// for Laravel projects. It handles Composer packages, Node packages, and copies
// predefined configuration files.

class MiseCommand extends BaseCommand
{
    // ----
    // Command Definition
    // ----

    protected $signature = 'laravel:mise
        {--force : Override existing files and skip confirmation prompts}';

    protected $description = 'Laravel Mise en Place';

    // ----
    // Post-Payload Commands Collection
    // ----

    /** @var array<array<string>> */
    protected array $postPayloadCommands = [];

    // ----
    // Constructor
    // ----

    public function __construct(
        private readonly ProcessService $process,
        private readonly ComposerJsonService $composerJson,
        private readonly PayloadService $payload,
        private readonly NodeDetector $nodeDetector,
    ) {
        parent::__construct();
    }

    // ----
    // Main Entry Point
    // ----

    public function handle(): int
    {
        $this->banner();

        //
        // Composer Package Installation
        // ----

        $this->h1('Installing Composer Packages');

        $this->out('<|gray>$> composer update</>');
        $result = $this->process->run(['composer', 'update']);
        if (! $result->successful()) {
            $this->nay('Failed to run composer update');
            $this->nay($result->errorOutput());

            return self::FAILURE;
        }

        $this->newLine();

        $composerPackages = config('laravel-mise.composer-packages');

        if (! is_array($composerPackages)) {
            $this->nay('Invalid composer packages configuration');

            return self::FAILURE;
        }

        /** @var array<string, array<string|array<string, array<array<string>>|array<string, mixed>>>> $composerPackages */
        if (! $this->installPackages($composerPackages, ['composer', 'require'], 'require-dev', '--dev')) {
            return self::FAILURE;
        }

        //
        // Node Package Installation
        // ----

        $packageManager = $this->nodeDetector->detect();
        $label = $packageManager->label();

        $this->h1("Installing {$label} Packages");

        $updateCommand = $packageManager->updateCommand();
        $this->out('<|gray>$> '.implode(' ', $updateCommand).'</>');
        $result = $this->process->run($updateCommand);
        if (! $result->successful()) {
            $this->nay('Failed to run package manager update');
            $this->nay($result->errorOutput());

            return self::FAILURE;
        }
        $this->newLine();

        $nodePackages = config('laravel-mise.node-packages');

        if (! is_array($nodePackages)) {
            $this->nay('Invalid node packages configuration');

            return self::FAILURE;
        }

        /** @var array<string, array<string|array<string, array<array<string>>|array<string, mixed>>>> $nodePackages */
        if (! $this->installPackages($nodePackages, $packageManager->installCommand(), 'devDependencies', '--save-dev')) {
            return self::FAILURE;
        }

        //
        // File Operations
        // ----

        $this->h1('Copying files');

        if (! $this->copyFiles()) {
            return self::FAILURE;
        }

        //
        // Post-Payload Commands

        if (! empty($this->postPayloadCommands)) {
            $this->h1('Run the following commands?');
            $this->execPostPayloadCommands($this->postPayloadCommands);
        }

        return self::SUCCESS;
    }

    // ----
    // Package Installation
    // ----

    /**
     * Install packages and handle their configurations.
     *
     * @param  array<string, array<string|array<string, array<array<string>>|array<string, mixed>>>>  $packages
     * @param  array<string>  $command
     */
    protected function installPackages(array $packages, array $command, string $devFlag = '', string $devFlagValue = '--dev'): bool
    {
        foreach ($packages as $type => $typePackages) {
            $commands = [];
            $packageNames = [];

            //
            // Package Processing
            // ----

            /** @var string|array<string, array<array<string>>> $v */
            foreach ($typePackages as $k => $v) {
                if (is_string($v)) {
                    $packageNames[] = $v;
                } else {
                    $packageNames[] = (string) $k;
                    if (isset($v['commands'])) {
                        $commands = [...$commands, ...$v['commands']];
                    }
                    if (isset($v['post_payload_commands'])) {
                        $this->postPayloadCommands = [...$this->postPayloadCommands, ...$v['post_payload_commands']];
                    }
                }
            }

            //
            // Command Execution
            // ----

            $baseCommand = $command;
            if ($type === $devFlag) {
                $baseCommand[] = $devFlagValue;
            }

            $allCommands = [[...$baseCommand, ...$packageNames], ...$commands];
            if (! $this->execCommands($allCommands)) {
                return false;
            }

            //
            // Composer.json Updates
            // ----

            /** @var string|array<string, array<array<string>>|array<string, mixed>> $v */
            foreach ($typePackages as $k => $v) {
                if (is_array($v) && isset($v['composer'])) {
                    $packageName = (string) $k;
                    /** @var array<mixed> $composerConfig */
                    $composerConfig = $v['composer'];

                    $sections = array_keys($composerConfig);
                    $sectionNames = implode(', ', $sections);
                    $this->out("<|gray>Adding {$packageName} configuration to composer.json ({$sectionNames})...</>");

                    try {
                        $result = $this->composerJson->update($composerConfig);
                        if ($result['updated']) {
                            $this->yay('Updated composer.json sections: '.implode(', ', $result['sections']));
                        } else {
                            $this->out('<|gray>No changes needed for composer.json</>');
                        }
                    } catch (\RuntimeException $e) {
                        $this->warning("Failed to update composer.json for {$packageName}: ".$e->getMessage());
                    }
                }
            }
        }

        return true;
    }

    // ----
    // External Process Execution
    // ----

    /**
     * Execute multiple commands in sequence.
     *
     * @param  array<array<string>>  $commands
     */
    protected function execCommands(array $commands): bool
    {
        foreach ($commands as $command) {
            $this->out('<|gray>$> '.implode(' ', $command).'</>');
            $result = $this->process->run($command);
            if (! $result->successful()) {
                if (! defined('PHPUNIT_COMPOSER_INSTALL')) {
                    $this->nay('Failed to run command');
                    $this->nay($result->errorOutput());
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Execute post-payload commands that don't fail the installation.
     *
     * @param  array<array<string>>  $commands
     */
    protected function execPostPayloadCommands(array $commands): void
    {
        if (empty($commands)) {
            return;
        }

        $this->out('<|gray>Executing all post-payload commands...</>');
        foreach ($commands as $command) {
            $this->out('<|gray>$> '.implode(' ', $command).'</>');
            $result = $this->process->run($command);
            if (! $result->successful()) {
                $this->out('<|gray>Post-payload command failed: '.implode(' ', $command).'</>');
                if ($result->errorOutput()) {
                    $this->out('<|gray>'.$result->errorOutput().'</>');
                }
                $this->out('<|gray>Continuing...</>');
            }
        }
    }

    // ----
    // File Operations
    // ----

    /**
     * Copy all files from the payload directory to the project.
     */
    protected function copyFiles(): bool
    {
        try {
            $force = (bool) $this->option('force');
            $results = $this->payload->copyAll($force);

            foreach ($results as $relativePath => $result) {
                match ($result) {
                    CopyResultEnum::Created => $this->yay("new {$relativePath}"),
                    CopyResultEnum::Overwritten => $this->yay("override {$relativePath}"),
                    CopyResultEnum::Skipped => $this->warning("skip {$relativePath}"),
                };
            }

            return true;
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return false;
        }
    }
}
