<?php

declare(strict_types=1);

namespace LaravelMise\Services;

use Illuminate\Filesystem\Filesystem;

//
// Composer JSON Service - composer.json Management
// ----
//
// Reads, modifies, and writes composer.json files.
// Returns change information for callers to handle output formatting.

readonly class ComposerJsonService
{
    public function __construct(
        private Filesystem $fs,
    ) {}

    //
    // Read/Write Operations
    // ----

    /**
     * Read and parse composer.json.
     *
     * @return array<mixed>
     *
     * @throws \RuntimeException
     */
    public function read(?string $path = null): array
    {
        $composerPath = $path ?? base_path('composer.json');

        if (! $this->fs->exists($composerPath)) {
            throw new \RuntimeException('composer.json not found');
        }

        $content = $this->fs->get($composerPath);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw new \RuntimeException('Invalid composer.json structure');
        }

        return $data;
    }

    /**
     * Write data to composer.json with proper formatting.
     *
     * @param  array<mixed>  $data
     *
     * @throws \RuntimeException
     */
    public function write(array $data, ?string $path = null): void
    {
        $composerPath = $path ?? base_path('composer.json');

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode composer.json data');
        }

        $this->fs->put($composerPath, $json."\n");
    }

    //
    // Section Updates
    // ----

    /**
     * Update composer.json with package-specific configuration.
     *
     * @param  array<mixed>  $config
     * @return array{updated: bool, sections: array<string>}
     */
    public function update(array $config, ?string $path = null): array
    {
        $data = $this->read($path);
        $sectionsUpdated = [];

        foreach ($config as $section => $sectionData) {
            if (! is_array($sectionData)) {
                continue;
            }

            $updated = match ($section) {
                'scripts' => $this->updateScripts($data, $sectionData)['updated'],
                'repositories' => $this->updateRepositories($data, $sectionData)['updated'],
                'config', 'extra' => $this->updateObjectSection($data, $section, $sectionData),
                default => false,
            };

            if ($updated) {
                $sectionsUpdated[] = $section;
            }
        }

        if (! empty($sectionsUpdated)) {
            $this->write($data, $path);
        }

        return ['updated' => ! empty($sectionsUpdated), 'sections' => $sectionsUpdated];
    }

    /**
     * Update the scripts section.
     *
     * @param  array<mixed>  $data
     * @param  array<mixed>  $scripts
     * @return array{updated: bool, added: array<string, array<int, string>>}
     */
    public function updateScripts(array &$data, array $scripts): array
    {
        if (! isset($data['scripts']) || ! is_array($data['scripts'])) {
            $data['scripts'] = [];
        }

        $updated = false;
        /** @var array<string, array<int, string>> $added */
        $added = [];

        foreach ($scripts as $scriptName => $commands) {
            if (! is_array($commands)) {
                continue;
            }

            $scriptKey = (string) $scriptName;

            /** @var array<int, string> $stringCommands */
            $stringCommands = array_values(array_filter($commands, is_string(...)));

            if (! isset($data['scripts'][$scriptKey])) {
                $data['scripts'][$scriptKey] = $stringCommands;
                $added[$scriptKey] = $stringCommands;
                $updated = true;
            } else {
                $existing = $data['scripts'][$scriptKey];

                if (is_string($existing)) {
                    $existing = [$existing];
                }

                if (! is_array($existing)) {
                    continue;
                }

                $commandsAdded = [];
                foreach ($stringCommands as $command) {
                    if (! $this->commandExists($existing, $command)) {
                        $existing[] = $command;
                        $commandsAdded[] = $command;
                    }
                }

                if (! empty($commandsAdded)) {
                    $data['scripts'][$scriptKey] = $existing;
                    $added[$scriptKey] = $commandsAdded;
                    $updated = true;
                }
            }
        }

        return ['updated' => $updated, 'added' => $added];
    }

    /**
     * Update the repositories section.
     *
     * @param  array<mixed>  $data
     * @param  array<mixed>  $repositories
     * @return array{updated: bool, added: array<string>}
     */
    public function updateRepositories(array &$data, array $repositories): array
    {
        if (! isset($data['repositories']) || ! is_array($data['repositories'])) {
            $data['repositories'] = [];
        }

        $updated = false;
        $added = [];

        foreach ($repositories as $repository) {
            if (! is_array($repository) || ! isset($repository['url'])) {
                continue;
            }

            $exists = array_any(
                $data['repositories'],
                fn ($existing) => is_array($existing)
                    && isset($existing['url'])
                    && $existing['url'] === $repository['url']
            );

            if (! $exists) {
                $data['repositories'][] = $repository;
                $url = is_string($repository['url']) ? $repository['url'] : 'unknown';
                $added[] = $url;
                $updated = true;
            }
        }

        return ['updated' => $updated, 'added' => $added];
    }

    /**
     * Update object sections (config, extra, etc.).
     *
     * @param  array<mixed>  $data
     * @param  array<mixed>  $sectionData
     */
    public function updateObjectSection(array &$data, string $section, array $sectionData): bool
    {
        if (! isset($data[$section]) || ! is_array($data[$section])) {
            $data[$section] = [];
        }

        $original = $data[$section];
        $data[$section] = array_replace_recursive($data[$section], $sectionData);

        return $original !== $data[$section];
    }

    //
    // Utility Methods
    // ----

    /**
     * Check if a command already exists in the commands array.
     *
     * @param  array<int|string, mixed>  $commands
     */
    private function commandExists(array $commands, string $targetCommand): bool
    {
        $normalizedTarget = $this->normalizeCommand($targetCommand);

        return array_any(
            $commands,
            fn ($command) => is_string($command)
                && $this->normalizeCommand($command) === $normalizedTarget
        );
    }

    /**
     * Normalize command string for comparison.
     */
    private function normalizeCommand(string $command): string
    {
        $normalized = preg_replace('/\s+/', ' ', $command);

        return trim($normalized ?? $command);
    }
}
