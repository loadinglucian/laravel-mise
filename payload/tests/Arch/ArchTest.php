<?php

declare(strict_types=1);

//
// Global Debugging Prevention
// ----

arch()
    ->expect('App')
    ->not->toUse([
        'dd',
        'dump',
        'var_dump',
        'print_r',
        'die',
        'exit',
    ]);

//
// Preset Architecture Rules
// ----

arch()->preset()->php();
arch()->preset()->laravel();
arch()->preset()->security();

//
// Strict Layered Architecture Enforcement
// ----

// Layer 1: Controllers must NOT directly use Models or Repositories
arch()
    ->expect('App\Http\Controllers')
    ->not->toUse([
        'App\Models',
        'App\Repositories',
        'App\Services',
        'Illuminate\Database\Eloquent',
    ]);

// Layer 1: Livewire Components must NOT directly use Models or Repositories
arch()
    ->expect('App\Livewire')
    ->not->toUse([
        'App\Models',
        'App\Repositories',
        'App\Services',
        'Illuminate\Database\Eloquent',
    ]);

// Layer 2: Actions must NOT directly use Models or Repositories
arch()
    ->expect('App\Actions')
    ->not->toUse([
        'App\Models',
        'App\Repositories',
        'Illuminate\Database\Eloquent',
    ]);

// Layer 2: Services must NOT directly use Models (should use Repositories) or Actions
arch()
    ->expect('App\Services')
    ->not->toUse([
        'App\Actions',
        'App\Models',
        'Illuminate\Database\Eloquent',
    ]);

//
// Directory Structure Enforcement
// ----

arch()
    ->expect('App\Models')
    ->toExtend(\Illuminate\Database\Eloquent\Model::class);

arch()
    ->expect('App\Exceptions')
    ->toExtend('Exception');

//
// Naming Conventions Enforcement
// ----

arch()
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch()
    ->expect('App\Actions')
    ->toHaveSuffix('Action');

arch()
    ->expect('App\Services')
    ->toHaveSuffix('Service');

arch()
    ->expect('App\Repositories')
    ->toHaveSuffix('Repository');

arch()
    ->expect('App\Exceptions')
    ->toHaveSuffix('Exception');

//
// Interface and Contract Enforcement
// ----

arch()
    ->expect('App\Repositories')
    ->toImplement(\App\Contracts\RepositoryInterface::class);

//
// Conditional Architecture Tests
// ----

// For conditional tests that require complex logic, we keep traditional test format
it('controllers extend base controller when it exists', function (): void {
    if (class_exists(\App\Http\Controllers\Controller::class)) {
        arch()
            ->expect('App\Http\Controllers')
            ->toExtend(\App\Http\Controllers\Controller::class);
    } else {
        expect(true)->toBeTrue(); // Skip if base controller doesn't exist
    }
});

it('livewire components extend base component when livewire is installed', function (): void {
    if (class_exists('Livewire\Component')) {
        arch()
            ->expect('App\Livewire')
            ->toExtend('Livewire\Component');
    } else {
        expect(true)->toBeTrue(); // Skip if Livewire not installed
    }
});

it('services implement service contracts when defined', function (): void {
    arch()
        ->expect('App\Services')
        ->classes()
        ->when(fn (ReflectionClass $class): bool => interface_exists("App\\Contracts\\{$class->getShortName()}Interface"))
        ->toImplement(fn (ReflectionClass $class): string => "App\\Contracts\\{$class->getShortName()}Interface");
});
