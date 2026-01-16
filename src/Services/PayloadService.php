<?php

declare(strict_types=1);

namespace LaravelMise\Services;

use LaravelMise\Enums\CopyResultEnum;
use Illuminate\Filesystem\Filesystem;

//
// Payload Service - File Discovery & Copying
// ----
//
// Discovers files from the payload directory and copies them to the project.
// Returns structured results for callers to handle output formatting.

readonly class PayloadService
{
    public function __construct(
        private Filesystem $fs,
    ) {}

    //
    // File Discovery
    // ----

    /**
     * Get the base path for payload files.
     */
    public function getBasePath(): string
    {
        return __DIR__.'/../../payload/';
    }

    /**
     * Get all files from the payload directory recursively.
     *
     * @return array<int, string>
     */
    public function getFiles(): array
    {
        $basePath = $this->getBasePath();

        /** @var \RecursiveIteratorIterator<\RecursiveDirectoryIterator> $payloadFiles */
        $payloadFiles = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $basePath,
                \RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        $files = [];
        foreach ($payloadFiles as $file) {
            /** @var \SplFileInfo $file */
            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    //
    // File Operations
    // ----

    /**
     * Copy a single file to its destination.
     *
     * @throws \RuntimeException
     */
    public function copy(string $source, string $dest, bool $force = false): CopyResultEnum
    {
        $destDir = dirname($dest);

        // Check if file exists and skip if not forced
        if ($this->fs->exists($dest) && ! $force) {
            return CopyResultEnum::Skipped;
        }

        $wasExisting = $this->fs->exists($dest);

        // Ensure destination directory exists
        if (! $this->fs->isDirectory($destDir)) {
            if (! $this->fs->makeDirectory($destDir, 0755, true)) {
                throw new \RuntimeException("Failed to create directory: {$destDir}");
            }
        }

        // Read source file
        $contents = $this->fs->get($source);

        // Write to destination
        if ($this->fs->put($dest, $contents) === false) {
            throw new \RuntimeException("Failed to write file: {$dest}");
        }

        return $wasExisting ? CopyResultEnum::Overwritten : CopyResultEnum::Created;
    }

    /**
     * Copy all payload files to the project.
     *
     * @return array<string, CopyResultEnum>
     *
     * @throws \RuntimeException
     */
    public function copyAll(bool $force = false): array
    {
        $basePath = $this->getBasePath();
        $files = $this->getFiles();
        $results = [];

        foreach ($files as $filePathname) {
            $relativePath = str_replace($basePath, '', $filePathname);
            $destPathname = base_path($relativePath);

            $result = $this->copy($filePathname, $destPathname, $force);
            $results[$relativePath] = $result;
        }

        return $results;
    }
}
