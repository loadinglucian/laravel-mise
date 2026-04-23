# Laravel Mise Rules

We're building Laravel Mise, a Laravel package that installs and configures a curated set of development tools via a single Artisan command.

The name references "mise en place" — having everything ready before you start.

We affectionately refer to it as just "mise".

> **IMPORTANT**
>
> - All services are registered as singletons in `MiseServiceProvider`
> - Payload files are copied verbatim—they cannot contain dynamic content
> - Never call Process facade directly—always use `ProcessService`

## Package Commands

```bash
php artisan laravel:mise         # Install everything
php artisan laravel:mise --composer --npm --files --force
php artisan db:migrate           # Alias for migrate command
```

## Architecture

```
src/
├── MiseServiceProvider.php      # Registers commands and services
├── Commands/
│   ├── BaseCommand.php          # Shared output formatting (h1, hr, yay, nay)
│   ├── MiseCommand.php          # Main installation orchestrator
│   └── DbMigrateCommand.php     # Migration convenience alias
├── Services/
│   ├── ProcessService.php       # Command execution wrapper (TTY handling)
│   ├── ComposerJsonService.php  # Read/write/merge composer.json
│   ├── PayloadService.php       # Discover and copy files from payload/
│   └── NodeDetector.php         # Auto-detect npm/yarn/pnpm/bun
└── Enums/
    ├── CopyResultEnum.php       # Created | Overwritten | Skipped
    └── NodeEnum.php             # Package manager commands/lockfiles

config/
├── composer-packages.php        # Packages to install + post-install config
└── npm-packages.php             # NPM packages to install

payload/                         # Template files copied to target projects
├── .github/workflows/           # CI workflows (pest, phpstan, pint)
├── phpstan.neon, pint.json      # Tool configurations
└── tests/                       # Example test structure
```

## Key Patterns

### Service Architecture

Services are registered as singletons in `MiseServiceProvider`. They wrap Laravel facades for testability:
- `ProcessService` wraps `Process::run()` with TTY detection
- `ComposerJsonService` handles intelligent JSON merging (deduplicates scripts/repos)
- `PayloadService` discovers files in `payload/` and copies with force/skip logic

### Command Flow

`MiseCommand::handle()` orchestrates installation:
1. Install Composer packages → update `composer.json` (scripts, repos, config)
2. Detect package manager → install NPM packages
3. Copy payload files → run post-payload commands

### Configuration-Driven Installation

Packages in `config/composer-packages.php` can specify:
```php
'package/name' => [
    'commands' => ['artisan:command'],           // Run after install
    'post_payload_commands' => ['artisan:cmd'],  // Run after file copy
    'composer' => [                              // Merge into composer.json
        'scripts' => [...],
        'repositories' => [...],
    ],
]
```

## Examples

### Example: Adding a Composer Package

In `config/composer-packages.php`:

```php
'vendor/package' => [
    'commands' => ['artisan:publish --tag=config'],
    'composer' => [
        'scripts' => [
            'post-update-cmd' => ['@php artisan vendor:publish'],
        ],
    ],
],
```

### Example: Service Usage

```php
// ProcessService wraps Process facade with TTY detection
$this->processService->run('composer install');

// PayloadService discovers and copies template files
$this->payloadService->copy($targetPath, force: true);
```

### Example: Wrong (Bypassing ProcessService)

```php
// WRONG - direct Process facade call
Process::run('composer install');

// RIGHT - use ProcessService for testability
$this->processService->run('composer install');
```

### Example: Wrong (Modifying Payload Files)

```php
// WRONG - payload files are templates, not dynamic
file_put_contents('payload/config.php', $dynamicContent);

// RIGHT - payload files are static templates copied verbatim
// Dynamic content belongs in config/ or is set post-copy
```

## Testing

Tests use Orchestra Testbench. Process calls are mocked to avoid actual command execution.

```
tests/
├── Feature/Commands/            # Integration tests with mocked processes
├── Unit/Commands/               # Unit tests for command logic
├── Arch/                        # Architecture tests (pest arch)
└── Support/MiseCommandHelpers.php
```

## Standards

- Services wrap Laravel facades for testability (never call facades directly)
- Configuration-driven installation (packages defined in `config/`, not hardcoded)
- Payload files are static templates—no runtime generation
- `ComposerJsonService` handles all `composer.json` modifications (scripts, repos, config)

## Constraints

- Never call `Process` facade directly—use `ProcessService`
- Never modify `payload/` files during runtime
- Never add packages requiring interactive configuration
- Never duplicate entries when merging `composer.json` (service handles deduplication)
