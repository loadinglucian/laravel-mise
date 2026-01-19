<?php

//
// PayloadService Unit Tests - File discovery and copying
// ----

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use LaravelMise\Enums\CopyResultEnum;
use LaravelMise\Services\PayloadService;

describe('PayloadService', function (): void {
    beforeEach(function (): void {
        $this->fs = mock(Filesystem::class);
        $this->service = new PayloadService($this->fs);
    });

    //
    // getBasePath() tests
    // ----

    describe('getBasePath()', function (): void {
        it('returns path ending with payload directory', function (): void {
            // ACT
            $result = $this->service->getBasePath();

            // ASSERT
            expect($result)->toEndWith('/payload/');
        });
    });

    //
    // copy() tests
    // ----

    describe('copy()', function (): void {
        it('returns Skipped when file exists and force is false', function (): void {
            // ARRANGE
            $this->fs->shouldReceive('exists')->with('/dest/file.txt')->andReturn(true);

            // ACT
            $result = $this->service->copy('/src/file.txt', '/dest/file.txt', force: false);

            // ASSERT
            expect($result)->toBe(CopyResultEnum::Skipped);
        });

        it('returns Created when destination does not exist', function (): void {
            // ARRANGE
            $this->fs->shouldReceive('exists')->with('/dest/file.txt')->andReturn(false);
            $this->fs->shouldReceive('isDirectory')->with('/dest')->andReturn(true);
            $this->fs->shouldReceive('get')->with('/src/file.txt')->andReturn('content');
            $this->fs->shouldReceive('put')->with('/dest/file.txt', 'content')->andReturn(7);

            // ACT
            $result = $this->service->copy('/src/file.txt', '/dest/file.txt');

            // ASSERT
            expect($result)->toBe(CopyResultEnum::Created);
        });

        it('returns Overwritten when file exists and force is true', function (): void {
            // ARRANGE
            $this->fs->shouldReceive('exists')->with('/dest/file.txt')->andReturn(true);
            $this->fs->shouldReceive('isDirectory')->with('/dest')->andReturn(true);
            $this->fs->shouldReceive('get')->with('/src/file.txt')->andReturn('new content');
            $this->fs->shouldReceive('put')->with('/dest/file.txt', 'new content')->andReturn(11);

            // ACT
            $result = $this->service->copy('/src/file.txt', '/dest/file.txt', force: true);

            // ASSERT
            expect($result)->toBe(CopyResultEnum::Overwritten);
        });

        it('creates destination directory when it does not exist', function (): void {
            // ARRANGE
            $this->fs->shouldReceive('exists')->with('/dest/subdir/file.txt')->andReturn(false);
            $this->fs->shouldReceive('isDirectory')->with('/dest/subdir')->andReturn(false);
            $this->fs->shouldReceive('makeDirectory')->with('/dest/subdir', 0755, true)->andReturn(true);
            $this->fs->shouldReceive('get')->with('/src/file.txt')->andReturn('content');
            $this->fs->shouldReceive('put')->with('/dest/subdir/file.txt', 'content')->andReturn(7);

            // ACT
            $result = $this->service->copy('/src/file.txt', '/dest/subdir/file.txt');

            // ASSERT
            expect($result)->toBe(CopyResultEnum::Created);
        });

        it('throws RuntimeException when directory creation fails', function (): void {
            // ARRANGE
            $this->fs->shouldReceive('exists')->with('/dest/file.txt')->andReturn(false);
            $this->fs->shouldReceive('isDirectory')->with('/dest')->andReturn(false);
            $this->fs->shouldReceive('makeDirectory')->with('/dest', 0755, true)->andReturn(false);

            // ACT & ASSERT
            expect(fn () => $this->service->copy('/src/file.txt', '/dest/file.txt'))
                ->toThrow(RuntimeException::class, 'Failed to create directory: /dest');
        });

        it('throws RuntimeException when file write fails', function (): void {
            // ARRANGE
            $this->fs->shouldReceive('exists')->with('/dest/file.txt')->andReturn(false);
            $this->fs->shouldReceive('isDirectory')->with('/dest')->andReturn(true);
            $this->fs->shouldReceive('get')->with('/src/file.txt')->andReturn('content');
            $this->fs->shouldReceive('put')->with('/dest/file.txt', 'content')->andReturn(false);

            // ACT & ASSERT
            expect(fn () => $this->service->copy('/src/file.txt', '/dest/file.txt'))
                ->toThrow(RuntimeException::class, 'Failed to write file: /dest/file.txt');
        });
    });

    //
    // Note: getFiles() and copyAll() use native PHP iterators which are difficult
    // to mock. These are tested indirectly via MiseCommand feature tests.
});
