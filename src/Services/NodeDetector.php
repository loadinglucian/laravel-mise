<?php

declare(strict_types=1);

namespace LaravelMise\Services;

use LaravelMise\Enums\NodeEnum;
use Illuminate\Filesystem\Filesystem;

//
// Node Detector - Package Manager Detection
// ----
//
// Detects which Node package manager the project uses by checking lock files.

readonly class NodeDetector
{
    public function __construct(
        private Filesystem $fs,
    ) {}

    //
    // Detection
    // ----

    /**
     * Detect the package manager used by the project.
     */
    public function detect(): NodeEnum
    {
        foreach (NodeEnum::cases() as $manager) {
            // NPM is the fallback
            if ($manager === NodeEnum::NPM) {
                continue;
            }

            foreach ($manager->lockFiles() as $lockFile) {
                if ($this->fs->exists(base_path($lockFile))) {
                    return $manager;
                }
            }
        }

        return NodeEnum::NPM;
    }
}
