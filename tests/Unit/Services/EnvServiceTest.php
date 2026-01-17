<?php

//
// EnvService Unit Tests - Variable replacement logic
// ----

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use LaravelMise\Services\EnvService;

describe('EnvService', function (): void {
    beforeEach(function (): void {
        $this->fs = mock(Filesystem::class);
        $this->service = new EnvService($this->fs);
    });

    //
    // updateVariables() tests
    // ----

    describe('updateVariables()', function (): void {
        it('replaces existing variable preserving surrounding content', function (): void {
            // ARRANGE
            $original = "APP_NAME=Laravel\nSESSION_DRIVER=file\nAPP_DEBUG=true\n";
            $this->fs->shouldReceive('get')->with('/path/.env')->andReturn($original);

            $writtenContent = '';
            $this->fs->shouldReceive('put')
                ->with('/path/.env', Mockery::capture($writtenContent));

            // ACT
            $result = $this->service->updateVariables('/path/.env', ['SESSION_DRIVER' => 'cookie']);

            // ASSERT - verify exact content transformation
            expect($writtenContent)->toBe("APP_NAME=Laravel\nSESSION_DRIVER=cookie\nAPP_DEBUG=true\n")
                ->and($result)->toBe(['SESSION_DRIVER' => true]);
        });

        it('appends new variable with proper newline handling', function (): void {
            // ARRANGE
            $original = "APP_NAME=Laravel\n";
            $this->fs->shouldReceive('get')->with('/path/.env')->andReturn($original);

            $writtenContent = '';
            $this->fs->shouldReceive('put')
                ->with('/path/.env', Mockery::capture($writtenContent));

            // ACT
            $result = $this->service->updateVariables('/path/.env', ['SESSION_ENCRYPT' => 'true']);

            // ASSERT - verify appended at end with newline
            expect($writtenContent)->toBe("APP_NAME=Laravel\nSESSION_ENCRYPT=true\n")
                ->and($result)->toBe(['SESSION_ENCRYPT' => false]);
        });

        it('handles mixed replace and append operations', function (): void {
            // ARRANGE
            $original = "SESSION_DRIVER=file\nAPP_DEBUG=true\n";
            $this->fs->shouldReceive('get')->with('/path/.env')->andReturn($original);

            $writtenContent = '';
            $this->fs->shouldReceive('put')
                ->with('/path/.env', Mockery::capture($writtenContent));

            // ACT
            $result = $this->service->updateVariables('/path/.env', [
                'SESSION_DRIVER' => 'cookie',    // exists - replace
                'SESSION_ENCRYPT' => 'true',     // new - append
            ]);

            // ASSERT - verify both operations and result map
            expect($writtenContent)->toBe("SESSION_DRIVER=cookie\nAPP_DEBUG=true\nSESSION_ENCRYPT=true\n")
                ->and($result)->toBe(['SESSION_DRIVER' => true, 'SESSION_ENCRYPT' => false]);
        });

        it('adds newline before appending to file without trailing newline', function (): void {
            // ARRANGE
            $original = 'APP_NAME=Laravel';  // No trailing newline
            $this->fs->shouldReceive('get')->with('/path/.env')->andReturn($original);

            $writtenContent = '';
            $this->fs->shouldReceive('put')
                ->with('/path/.env', Mockery::capture($writtenContent));

            // ACT
            $this->service->updateVariables('/path/.env', ['SESSION_DRIVER' => 'cookie']);

            // ASSERT - rtrim adds newline before new var
            expect($writtenContent)->toBe("APP_NAME=Laravel\nSESSION_DRIVER=cookie\n");
        });

        it('handles empty file', function (): void {
            // ARRANGE
            $this->fs->shouldReceive('get')->with('/path/.env')->andReturn('');

            $writtenContent = '';
            $this->fs->shouldReceive('put')
                ->with('/path/.env', Mockery::capture($writtenContent));

            // ACT
            $result = $this->service->updateVariables('/path/.env', ['SESSION_DRIVER' => 'cookie']);

            // ASSERT
            expect($writtenContent)->toBe("\nSESSION_DRIVER=cookie\n")
                ->and($result)->toBe(['SESSION_DRIVER' => false]);
        });

        it('replaces variable with special regex characters in value', function (): void {
            // ARRANGE - value contains regex metacharacters
            $original = "APP_URL=http://localhost\n";
            $this->fs->shouldReceive('get')->with('/path/.env')->andReturn($original);

            $writtenContent = '';
            $this->fs->shouldReceive('put')
                ->with('/path/.env', Mockery::capture($writtenContent));

            // ACT
            $this->service->updateVariables('/path/.env', ['APP_URL' => 'https://example.com/app?foo=bar']);

            // ASSERT - special chars don't break regex
            expect($writtenContent)->toBe("APP_URL=https://example.com/app?foo=bar\n");
        });
    });
});
