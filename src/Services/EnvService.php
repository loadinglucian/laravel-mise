<?php

declare(strict_types=1);

namespace LaravelMise\Services;

use Illuminate\Filesystem\Filesystem;

//
// Env Service - Environment File Discovery & Modification
// ----
//
// Discovers .env* files and updates environment variables.
// Returns structured results for callers to handle output formatting.

readonly class EnvService
{
    public function __construct(
        private Filesystem $fs,
    ) {}

    //
    // File Discovery
    // ----

    /**
     * Get all .env* files in the given directory.
     *
     * @return array<int, string>
     */
    public function getEnvFiles(string $directory): array
    {
        $pattern = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.env*';

        /** @var list<string>|false $files */
        $files = glob($pattern, GLOB_NOSORT);

        if ($files === false) {
            return [];
        }

        // Filter out directories (e.g., .env.d/)
        return array_values(array_filter($files, $this->fs->isFile(...)));
    }

    //
    // Variable Operations
    // ----

    /**
     * Update environment variables in a file.
     *
     * @param  array<string, string>  $variables  Key-value pairs to update
     * @return array<string, bool> Map of variable name to whether it was updated (true) or added (false)
     */
    public function updateVariables(string $path, array $variables): array
    {
        /** @var string $contents */
        $contents = $this->fs->get($path);
        $results = [];

        foreach ($variables as $key => $value) {
            $pattern = '/^'.preg_quote($key, '/').'=.*/m';
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, (string) $contents) === 1) {
                // Variable exists - replace it
                /** @var string $newContents */
                $newContents = preg_replace($pattern, $replacement, (string) $contents);
                $contents = $newContents;
                $results[$key] = true;
            } else {
                // Variable doesn't exist - append it
                $contents = rtrim((string) $contents)."\n{$replacement}\n";
                $results[$key] = false;
            }
        }

        $this->fs->put($path, $contents);

        return $results;
    }
}
