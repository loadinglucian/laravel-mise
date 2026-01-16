<?php

declare(strict_types=1);

namespace LaravelMise\Enums;

enum NodeEnum: string
{
    case NPM = 'npm';
    case YARN = 'yarn';
    case PNPM = 'pnpm';
    case BUN = 'bun';

    /**
     * Get the install command for the package manager.
     *
     * @return array<int, string>
     */
    public function installCommand(): array
    {
        return match ($this) {
            self::NPM => ['npm', 'install'],
            self::YARN => ['yarn', 'install'],
            self::PNPM => ['pnpm', 'install'],
            self::BUN => ['bun', 'install'],
        };
    }

    /**
     * Get the update command for the package manager.
     *
     * @return array<int, string>
     */
    public function updateCommand(): array
    {
        return match ($this) {
            self::NPM => ['npm', 'update'],
            self::YARN => ['yarn', 'upgrade'],
            self::PNPM => ['pnpm', 'update'],
            self::BUN => ['bun', 'update'],
        };
    }

    /**
     * Get the lock files associated with the package manager.
     *
     * @return array<int, string>
     */
    public function lockFiles(): array
    {
        return match ($this) {
            self::NPM => ['package-lock.json'],
            self::YARN => ['yarn.lock'],
            self::PNPM => ['pnpm-lock.yaml'],
            self::BUN => ['bun.lock', 'bun.lockb'],
        };
    }

    /**
     * Get the human-readable label for the package manager.
     */
    public function label(): string
    {
        return match ($this) {
            self::NPM => 'NPM',
            self::YARN => 'Yarn',
            self::PNPM => 'PNPM',
            self::BUN => 'Bun',
        };
    }
}
