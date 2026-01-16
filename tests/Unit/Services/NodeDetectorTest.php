<?php

//
// NodeDetector Unit Tests - Package manager detection
// ----

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use LaravelMise\Enums\NodeEnum;
use LaravelMise\Services\NodeDetector;

describe('NodeDetector', function (): void {
    beforeEach(function (): void {
        $this->fs = mock(Filesystem::class);
        $this->detector = new NodeDetector($this->fs);
    });

    //
    // detect() tests
    // ----

    describe('detect()', function (): void {
        it('returns NPM when no lock files exist', function (): void {
            // ARRANGE
            $this->fs->shouldReceive('exists')->andReturn(false);

            // ACT
            $result = $this->detector->detect();

            // ASSERT
            expect($result)->toBe(NodeEnum::NPM);
        });

        it('returns PNPM when pnpm-lock.yaml exists', function (): void {
            // ARRANGE
            $this->fs->shouldReceive('exists')
                ->andReturnUsing(fn (string $path) => str_contains($path, 'pnpm-lock.yaml'));

            // ACT
            $result = $this->detector->detect();

            // ASSERT
            expect($result)->toBe(NodeEnum::PNPM);
        });

        it('returns BUN when bun.lock exists', function (): void {
            // ARRANGE
            $this->fs->shouldReceive('exists')
                ->andReturnUsing(fn (string $path) => str_contains($path, 'bun.lock'));

            // ACT
            $result = $this->detector->detect();

            // ASSERT
            expect($result)->toBe(NodeEnum::BUN);
        });

        it('returns YARN when yarn.lock exists', function (): void {
            // ARRANGE
            $this->fs->shouldReceive('exists')
                ->andReturnUsing(fn (string $path) => str_contains($path, 'yarn.lock'));

            // ACT
            $result = $this->detector->detect();

            // ASSERT
            expect($result)->toBe(NodeEnum::YARN);
        });
    });
});
