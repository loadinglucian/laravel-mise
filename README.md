# Laravel Mise en Place

- [Introduction](#introduction)
- [Requirements](#requirements)
- [Installation](#installation)
    - [Install Everything](#install-everything)
- [Quickstart](#quickstart)
- [What Gets Installed](#what-gets-installed)
    - [Composer Packages](#composer-packages)
    - [Node Packages](#node-packages)
    - [Configuration Files](#configuration-files)
- [Command Options](#command-options)
- [Additional Features](#additional-features)
    - [Session Configuration](#session-configuration)
    - [Database Migration Alias](#database-migration-alias)
    - [Automatic Composer.json Configuration](#automatic-composerjson-configuration)
- [Post-Installation](#post-installation)
    - [Automatic Code Quality Checks](#automatic-code-quality-checks)
    - [Additional Setup](#additional-setup)
- [License](#license)

<a name="introduction"></a>

## Introduction

In professional kitchens, chefs practice "mise en place" — a French phrase meaning "everything in its place." Before cooking begins, every ingredient is prepped, every tool is positioned, and the workspace is ready. This preparation transforms chaos into flow, allowing chefs to focus on creating rather than searching.

Laravel Mise brings this philosophy to your development environment. Instead of spending your first hour on a new project installing packages, configuring linters, and setting up GitHub workflows, you'll run a single command and get straight to building. The package installs a curated selection of modern development tools — Livewire, PHPStan, Pint, Pest, Prettier, and more — all pre-configured with sensible defaults.

<a name="requirements"></a>

## Requirements

- PHP ^8.5
- Laravel ^12.40

Don't worry — if you're running a recent Laravel installation, you'll already meet these requirements.

<a name="installation"></a>

## Installation

You may install the package via Composer:

```shell
composer require --dev loadinglucian/laravel-mise:dev-main
```

> [!NOTE]
> The package automatically registers itself via Laravel's package discovery. You don't need to add anything to your `config/app.php`.

<a name="install-everything"></a>

### Install Everything

To install all packages and copy all configuration files, run the mise command:

```shell
php artisan mise
```

The command installs Composer and NPM packages, copies configuration files to your project, and automatically configures your `composer.json` with useful scripts and settings.

<a name="quickstart"></a>

## Quickstart

To see Laravel Mise in action, let's walk through a typical installation. By following this example, you'll understand what happens when you run the command and what your project looks like afterward.

Start with a fresh Laravel project and run the mise command:

```shell
laravel new my-project
cd my-project
composer require --dev loadinglucian/laravel-mise:dev-main
php artisan mise
```

The command will:

1. Install Composer packages (Livewire, PHPStan, Pest, etc.)
2. Update your `composer.json` with scripts like `pint`, `pstan`, and `pest`
3. Detect your package manager (npm, yarn, pnpm, or bun) and install frontend dependencies
4. Copy configuration files for all your new tools
5. Run Pint, Rector, and PHPStan to ensure everything passes from the start

Once complete, you can immediately run code quality checks:

```shell
composer pall   # Run all checks: pint → rector → phpstan → pest
composer pint   # Fix code style issues
composer pstan  # Run static analysis
composer pest   # Run your test suite
```

<a name="what-gets-installed"></a>

## What Gets Installed

<a name="composer-packages"></a>

### Composer Packages

**Production Dependencies:**

These packages enhance your application's capabilities:

- `livewire/livewire` - Build dynamic interfaces without leaving PHP
- `livewire/flux` - Modern UI components designed for Livewire applications
- `spatie/laravel-data` - Create powerful data objects with validation and transformation

**Development Dependencies:**

These tools help you write better code and catch issues early:

- `barryvdh/laravel-debugbar` - Debug your application with an in-browser toolbar
- `barryvdh/laravel-ide-helper` - Get proper IDE autocompletion for Laravel's magic
- `beyondcode/laravel-query-detector` - Catch N+1 query problems before they hit production
- `rector/rector` - Automatically upgrade and refactor your codebase
- `laravel/pint` - Keep your code style consistent with zero configuration
- `larastan/larastan` - Find bugs through static analysis at PHPStan's maximum level
- `pestphp/pest` - Write expressive tests with a delightful syntax
- `pestphp/pest-plugin-arch` - Enforce architectural rules in your test suite
- `roave/security-advisories` - Block installation of packages with known vulnerabilities

<a name="node-packages"></a>

### Node Packages

**Dependencies:**

- `tailwindcss` - Build custom designs without writing custom CSS
- `@tailwindcss/vite` - Integrate Tailwind seamlessly with Vite

**Development Dependencies:**

- `prettier` - Format your code consistently across the team
- `prettier-plugin-blade` - Format Blade templates alongside your other files
- `prettier-plugin-tailwindcss` - Automatically sort Tailwind classes

<a name="configuration-files"></a>

### Configuration Files

The package copies pre-configured files to your project, organized by purpose:

**Code Quality:**

- `phpstan.neon` - PHPStan configuration at maximum strictness
- `pint.json` - Laravel Pint with the Laravel preset
- `rector.php` - Rector rules for automated refactoring
- `.prettierrc` - Prettier configuration for frontend files

**Testing:**

- `phpunit.xml` - PHPUnit configuration
- `tests/Pest.php` - Pest configuration
- `tests/FeatureTestCase.php` - Base test case for feature tests
- `tests/CICanary.php` - CI health check test
- `tests/Arch/ArchTest.php` - Architecture tests
- `tests/Feature/ArchTest.php` - Feature architecture tests

**Application Scaffolding:**

- `app/Contracts/RepositoryInterface.php` - Repository pattern interface
- `app/Repositories/BaseRepository.php` - Base repository implementation

**Development:**

- `.editorconfig` - Consistent formatting across editors (indentation, line endings)
- `.cursorignore` - Cursor IDE ignore patterns
- `scripts/get-changed-php-files.sh` - Git helper for changed file detection

**GitHub Integration:**

- `.github/workflows/pest.yml` - Automated testing workflow
- `.github/workflows/phpstan.yml` - Static analysis workflow
- `.github/workflows/pint.yml` - Code style checking workflow
- `.github/workflows/rector.yml` - Automated refactoring workflow
- `.github/workflows/ci-canary.yml` - CI health check workflow
- `.github/workflows/dependabot-automerge.yml` - Auto-merge Dependabot PRs
- `.github/dependabot.yml` - Dependabot configuration
- `.github/rulesets/protect_main.json` - Branch protection ruleset
- `.github/actions/setup-bun/action.yml` - Reusable Bun setup action
- `.github/actions/setup-php-composer/action.yml` - Reusable PHP/Composer setup action

**Environment:**

- `.env*` files - Updates session configuration for security and scalability

<a name="command-options"></a>

## Command Options

| Option      | Description                   |
| ----------- | ----------------------------- |
| `--yes, -y` | Skip all confirmation prompts |

<a name="additional-features"></a>

## Additional Features

<a name="session-configuration"></a>

### Session Configuration

Laravel defaults to storing sessions in the database, which requires a migration and adds database overhead for every request. Laravel Mise updates your environment files to use encrypted cookie sessions instead:

- `SESSION_DRIVER=cookie` — Stores session data in encrypted cookies (no database queries)
- `SESSION_ENCRYPT=true` — Encrypts session data for security

This change applies to all `.env*` files in your project (`.env`, `.env.example`, `.env.testing`, etc.) to keep them consistent.

<a name="database-migration-alias"></a>

### Database Migration Alias

Maybe it's muscle memory from Rails days, but many developers instinctively type `db:migrate` and then stare blankly at Laravel's `Command "db:migrate" is not defined` error. It makes sense that migrations should live in the `db:*` namespace.

This package adds that missing command. It forwards everything to Laravel's standard `migrate` command:

```shell
# Run database migrations (equivalent to 'php artisan migrate')
php artisan db:migrate

# Pass any migrate options (e.g., --force, --seed, --step, etc.)
php artisan db:migrate --force --seed
```

<a name="automatic-composerjson-configuration"></a>

### Automatic Composer.json Configuration

When installing Composer packages, the command automatically configures your `composer.json` with settings that would otherwise require manual setup:

- **Scripts**: Adds `pint`, `pstan`, `rect`, `pest`, and `pall` commands
- **Repositories**: Configures any required package repositories
- **Configuration**: Updates config and extra sections as needed

This means you can run `composer pall` immediately after installation — no manual configuration required.

<a name="post-installation"></a>

## Post-Installation

<a name="automatic-code-quality-checks"></a>

### Automatic Code Quality Checks

The package runs code quality tools automatically after installation:

- **Laravel Pint** fixes code style issues throughout your project
- **Rector** applies automated refactoring rules
- **PHPStan** runs static analysis to identify potential issues

These checks ensure your codebase follows best practices from the very first commit.

<a name="additional-setup"></a>

### Additional Setup

Some packages require a few manual steps to complete their setup.

**Livewire Flux:**

The Flux configuration is published automatically during installation. However, you'll need to complete the setup manually:

> [!NOTE]
> For complete Flux setup including asset publishing and layout configuration, follow the [official Flux installation guide](https://fluxui.dev/docs/installation). You'll need to add the `@fluxAppearance` and `@fluxScripts` directives to your layout file.

<a name="license"></a>

## License

Laravel Mise is open-source software distributed under the [MIT License](./LICENSE).

You can use it freely for personal or commercial projects, without any restrictions.
