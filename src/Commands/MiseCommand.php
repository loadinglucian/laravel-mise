<?php

declare(strict_types=1);

namespace LaravelMise\Commands;

use LaravelMise\Enums\CopyResultEnum;
use LaravelMise\Services\ComposerJsonService;
use LaravelMise\Services\EnvService;
use LaravelMise\Services\NodeDetector;
use LaravelMise\Services\PayloadService;
use LaravelMise\Services\ProcessService;

use function Laravel\Prompts\confirm;

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

    protected $signature = 'mise
        {--y|yes : Skip confirmation prompt}';

    protected $description = 'Laravel Mise en Place';

    // ----
    // Post-Payload Commands Collection
    // ----

    /** @var array<array{command: array<string>, options: array<string>}> */
    protected array $postPayloadCommands = [];

    // ----
    // Constructor
    // ----

    public function __construct(
        private readonly ProcessService $process,
        private readonly ComposerJsonService $composerJson,
        private readonly PayloadService $payload,
        private readonly NodeDetector $nodeDetector,
        private readonly EnvService $env,
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
        // Confirmation Prompt
        // ----

        if (! $this->option('yes')) {
            /** @var array<string, array<mixed>> $composerPackages */
            $composerPackages = config('laravel-mise.composer-packages');
            /** @var array<string, array<mixed>> $nodePackages */
            $nodePackages = config('laravel-mise.node-packages');

            $composerCount = $this->countPackages($composerPackages);
            $nodeCount = $this->countPackages($nodePackages);
            $fileCount = count($this->payload->getFiles());

            $this->newLine();
            $this->out('This will install:');
            $this->out("  • {$composerCount} Composer packages");
            $this->out("  • {$nodeCount} Node packages");
            $this->out("  • {$fileCount} configuration files");
            $this->newLine();

            if (! confirm('Do you want to continue?', default: true)) {
                $this->out('<|gray>Installation cancelled.</>');

                return self::SUCCESS;
            }

            $this->newLine();
        }

        //
        // Composer Package Installation
        // ----

        $this->h1('Installing Composer Packages');

        $this->out('<|gray>$> composer update</>');
        $result = $this->process->runWithOutput(
            ['composer', 'update'],
            fn (string $line) => $this->line($line)
        );
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
        $result = $this->process->runWithOutput(
            $updateCommand,
            fn (string $line) => $this->line($line)
        );
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
        // Environment Configuration
        // ----

        $this->h1('Updating Environment Files');

        $envFiles = $this->env->getEnvFiles(base_path());
        $sessionConfig = [
            'SESSION_DRIVER' => 'cookie',
            'SESSION_ENCRYPT' => 'true',
        ];

        if (empty($envFiles)) {
            $this->warning('No .env files found');
        } else {
            foreach ($envFiles as $file) {
                $this->env->updateVariables($file, $sessionConfig);
                $this->yay('Updated '.basename($file));
            }
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
     * Extract command options from package configuration.
     *
     * @param  array<string, mixed>  $packageConfig
     * @return array<string>
     */
    protected function extractOptions(array $packageConfig): array
    {
        /** @var array<string> $options */
        $options = [];

        if (isset($packageConfig['command_options']) && is_array($packageConfig['command_options'])) {
            foreach ($packageConfig['command_options'] as $option) {
                if (is_string($option)) {
                    $options[] = $option;
                }
            }
        }

        return $options;
    }

    /**
     * Install packages and handle their configurations.
     *
     * @param  array<string, array<string|array<string, array<array<string>>|array<string, mixed>>>>  $packages
     * @param  array<string>  $command
     */
    protected function installPackages(array $packages, array $command, string $devFlag = '', string $devFlagValue = '--dev'): bool
    {
        foreach ($packages as $type => $typePackages) {
            $packageNames = [];

            /** @var array<array{commands: array<array<string>>, options: array<string>}> */
            $packageCommands = [];

            //
            // Package Processing
            // ----

            /** @var string|array<string, array<array<string>>|string> $v */
            foreach ($typePackages as $k => $v) {
                if (is_string($v)) {
                    $packageNames[] = $v;
                } else {
                    //
                    // Skip packages with unmet requirements

                    if (isset($v['requires'])) {
                        $requiredPackage = $v['requires'];
                        if (is_string($requiredPackage) && ! $this->composerJson->hasPackage($requiredPackage)) {
                            continue;
                        }
                    }

                    $packageNames[] = (string) $k;

                    //
                    // Collect package commands with their specific options

                    if (isset($v['commands']) && is_array($v['commands'])) {
                        $packageOptions = $this->extractOptions($v);
                        $packageCommands[] = [
                            'commands' => $v['commands'],
                            'options' => $packageOptions,
                        ];
                    }

                    //
                    // Collect post-payload commands with their specific options

                    if (isset($v['post_payload_commands']) && is_array($v['post_payload_commands'])) {
                        $packageOptions = $this->extractOptions($v);
                        foreach ($v['post_payload_commands'] as $postCmd) {
                            $this->postPayloadCommands[] = [
                                'command' => $postCmd,
                                'options' => $packageOptions,
                            ];
                        }
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

            //
            // Execute package manager command (without command options)

            $packageManagerCommand = [...$baseCommand, ...$packageNames];
            if (! $this->execCommands([$packageManagerCommand])) {
                return false;
            }

            //
            // Execute each package's commands with only that package's options

            foreach ($packageCommands as $pkgCmd) {
                if (! $this->execCommands($pkgCmd['commands'], $pkgCmd['options'])) {
                    return false;
                }
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
     * @param  array<string>  $commandOptions
     */
    protected function execCommands(array $commands, array $commandOptions = []): bool
    {
        foreach ($commands as $command) {
            //
            // Inject dynamic options

            if (in_array('destination', $commandOptions, true)) {
                $command[] = '--destination';
                $command[] = base_path();
            }
            if (in_array('force', $commandOptions, true)) {
                $command[] = '--force';
            }

            $this->out('<|gray>$> '.implode(' ', $command).'</>');
            $result = $this->process->runWithOutput(
                $command,
                fn (string $line) => $this->line($line)
            );
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
     * @param  array<array{command: array<string>, options: array<string>}>  $commands
     */
    protected function execPostPayloadCommands(array $commands): void
    {
        if (empty($commands)) {
            return;
        }

        $this->out('<|gray>Executing all post-payload commands...</>');
        foreach ($commands as $cmdEntry) {
            $command = $cmdEntry['command'];
            $commandOptions = $cmdEntry['options'];

            //
            // Inject dynamic options

            if (in_array('destination', $commandOptions, true)) {
                $command[] = '--destination';
                $command[] = base_path();
            }
            if (in_array('force', $commandOptions, true)) {
                $command[] = '--force';
            }

            $this->out('<|gray>$> '.implode(' ', $command).'</>');
            $result = $this->process->runWithOutput(
                $command,
                fn (string $line) => $this->line($line)
            );
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
            $results = $this->payload->copyAll(force: true);

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

    // ----
    // Utility Methods
    // ----

    /**
     * Count total packages across all sections.
     *
     * @param  array<string, array<mixed>>  $packages
     */
    protected function countPackages(array $packages): int
    {
        $count = 0;
        foreach ($packages as $typePackages) {
            $count += count($typePackages);
        }

        return $count;
    }
}
