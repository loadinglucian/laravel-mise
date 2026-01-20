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
use function Laravel\Prompts\pause;

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

    protected $signature = 'mise {--y|yes : Skip confirmation prompt}';

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
            $this->out([
                '',
                'This will install a number of packages and files. Existing files will be overwritten.',
                'Make sure your working tree is clean so you can easily revert any changes if needed.',
            ]);

            if (! confirm('Do you want to continue?', default: true)) {
                $this->info('Installation cancelled.');

                return self::SUCCESS;
            }
        }

        //
        // Composer Package Installation
        // ----

        $this->h1('Installing Composer Packages');

        $this->runProcess(['composer', 'update']);

        $composerPackages = config('laravel-mise.composer-packages');

        if (! is_array($composerPackages)) {
            $this->nay('Invalid composer packages configuration');

            return self::FAILURE;
        }

        /** @var array<string, array<string|array<string, array<array<string>>|array<string, mixed>>>> $composerPackages */
        $this->installPackages($composerPackages, ['composer', 'require'], 'require-dev', '--dev');

        //
        // Node Package Installation
        // ----

        $packageManager = $this->nodeDetector->detect();
        $label = $packageManager->label();

        $this->h1("Installing {$label} Packages");

        $this->runProcess($packageManager->updateCommand());

        $nodePackages = config('laravel-mise.node-packages');

        if (! is_array($nodePackages)) {
            $this->nay('Invalid node packages configuration');

            return self::FAILURE;
        }

        /** @var array<string, array<string|array<string, array<array<string>>|array<string, mixed>>>> $nodePackages */
        $this->installPackages($nodePackages, $packageManager->installCommand(), 'devDependencies', '--save-dev');

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
            $this->h1('Running Post-Payload Commands');
            foreach ($this->postPayloadCommands as $cmdEntry) {
                $this->runProcesses([$cmdEntry['command']], $cmdEntry['options']);
            }
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
    protected function installPackages(array $packages, array $command, string $devFlag = '', string $devFlagValue = '--dev'): void
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
            $this->runProcesses([$packageManagerCommand]);

            //
            // Execute each package's commands with only that package's options

            foreach ($packageCommands as $pkgCmd) {
                $this->runProcesses($pkgCmd['commands'], $pkgCmd['options']);
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
                    $this->out("Adding {$packageName} configuration to composer.json ({$sectionNames})...");

                    try {
                        $result = $this->composerJson->update($composerConfig);
                        if ($result['updated']) {
                            $this->yay('Updated composer.json sections: '.implode(', ', $result['sections']));
                        } else {
                            $this->out('No changes needed for composer.json');
                        }
                    } catch (\RuntimeException $e) {
                        $this->warning("Failed to update composer.json for {$packageName}: ".$e->getMessage());
                    }
                }
            }
        }
    }

    // ----
    // External Process Execution
    // ----

    /**
     * Execute a single command with output streaming.
     *
     * Displays the command, runs it, and on failure shows error + pauses.
     *
     * @param  array<string>  $command
     */
    protected function runProcess(array $command, bool $ignoreErrors = false): void
    {
        $this->out('$> '.implode(' ', $command));
        $result = $this->process->run(
            $command,
            fn (string $output) => $this->output->write($output)
        );

        if (! $result->successful() && ! $ignoreErrors) {
            $errorOutput = $result->errorOutput();
            if ($errorOutput) {
                $this->nay($errorOutput);
            }

            if (! app()->runningUnitTests()) {
                pause('Press ENTER to continue.');
            }
        }
    }

    /**
     * Execute multiple commands with optional dynamic options.
     *
     * @param  array<array<string>>  $commands
     * @param  array<string>  $commandOptions
     */
    protected function runProcesses(array $commands, array $commandOptions = []): void
    {
        $ignoreErrors = in_array('ignore_errors', $commandOptions, true);

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

            $this->runProcess($command, $ignoreErrors);
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
}
