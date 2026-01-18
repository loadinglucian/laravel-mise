<?php

//
// ComposerJsonService Unit Tests - composer.json manipulation
// ----

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use LaravelMise\Services\ComposerJsonService;

describe('ComposerJsonService', function (): void {
    beforeEach(function (): void {
        $this->fs = mock(Filesystem::class);
        $this->service = new ComposerJsonService($this->fs);
    });

    //
    // read() tests
    // ----

    describe('read()', function (): void {
        it('throws RuntimeException when file does not exist', function (): void {
            // ARRANGE
            $this->fs->shouldReceive('exists')->with('/path/composer.json')->andReturn(false);

            // ACT & ASSERT
            expect(fn () => $this->service->read('/path/composer.json'))
                ->toThrow(RuntimeException::class, 'composer.json not found');
        });

        it('throws JsonException when JSON is invalid', function (): void {
            // ARRANGE
            $this->fs->shouldReceive('exists')->with('/path/composer.json')->andReturn(true);
            $this->fs->shouldReceive('get')->with('/path/composer.json')->andReturn('not valid json');

            // ACT & ASSERT
            expect(fn () => $this->service->read('/path/composer.json'))
                ->toThrow(JsonException::class);
        });

        it('throws RuntimeException when JSON is not an array', function (): void {
            // ARRANGE
            $this->fs->shouldReceive('exists')->with('/path/composer.json')->andReturn(true);
            $this->fs->shouldReceive('get')->with('/path/composer.json')->andReturn('"just a string"');

            // ACT & ASSERT
            expect(fn () => $this->service->read('/path/composer.json'))
                ->toThrow(RuntimeException::class, 'Invalid composer.json structure');
        });

        it('returns parsed array from valid JSON', function (): void {
            // ARRANGE
            $this->fs->shouldReceive('exists')->with('/path/composer.json')->andReturn(true);
            $this->fs->shouldReceive('get')->with('/path/composer.json')->andReturn('{"name": "test/package"}');

            // ACT
            $result = $this->service->read('/path/composer.json');

            // ASSERT
            expect($result)->toBe(['name' => 'test/package']);
        });
    });

    //
    // hasPackage() tests
    // ----

    describe('hasPackage()', function (): void {
        it('returns true when package exists in require', function (): void {
            // ARRANGE
            $json = json_encode(['require' => ['livewire/livewire' => '^3.0']]);
            $this->fs->shouldReceive('exists')->with('/path/composer.json')->andReturn(true);
            $this->fs->shouldReceive('get')->with('/path/composer.json')->andReturn($json);

            // ACT
            $result = $this->service->hasPackage('livewire/livewire', '/path/composer.json');

            // ASSERT
            expect($result)->toBeTrue();
        });

        it('returns true when package exists in require-dev', function (): void {
            // ARRANGE
            $json = json_encode(['require-dev' => ['pestphp/pest' => '^2.0']]);
            $this->fs->shouldReceive('exists')->with('/path/composer.json')->andReturn(true);
            $this->fs->shouldReceive('get')->with('/path/composer.json')->andReturn($json);

            // ACT
            $result = $this->service->hasPackage('pestphp/pest', '/path/composer.json');

            // ASSERT
            expect($result)->toBeTrue();
        });

        it('returns false when package not found', function (): void {
            // ARRANGE
            $json = json_encode(['require' => ['laravel/framework' => '^11.0']]);
            $this->fs->shouldReceive('exists')->with('/path/composer.json')->andReturn(true);
            $this->fs->shouldReceive('get')->with('/path/composer.json')->andReturn($json);

            // ACT
            $result = $this->service->hasPackage('livewire/livewire', '/path/composer.json');

            // ASSERT
            expect($result)->toBeFalse();
        });

        it('returns false when require sections are not arrays', function (): void {
            // ARRANGE
            $json = json_encode(['require' => 'invalid', 'require-dev' => null]);
            $this->fs->shouldReceive('exists')->with('/path/composer.json')->andReturn(true);
            $this->fs->shouldReceive('get')->with('/path/composer.json')->andReturn($json);

            // ACT
            $result = $this->service->hasPackage('any/package', '/path/composer.json');

            // ASSERT
            expect($result)->toBeFalse();
        });
    });

    //
    // write() tests
    // ----

    describe('write()', function (): void {
        it('writes pretty-printed JSON with trailing newline', function (): void {
            // ARRANGE
            $data = ['name' => 'test/package', 'type' => 'library'];
            $expectedJson = "{\n    \"name\": \"test/package\",\n    \"type\": \"library\"\n}\n";

            $this->fs->shouldReceive('put')
                ->once()
                ->with('/path/composer.json', $expectedJson);

            // ACT
            $this->service->write($data, '/path/composer.json');

            // ASSERT (mock verification)
        });
    });

    //
    // updateScripts() tests
    // ----

    describe('updateScripts()', function (): void {
        it('adds new script to empty scripts section', function (): void {
            // ARRANGE
            $data = [];
            $scripts = ['test' => ['phpunit']];

            // ACT
            $result = $this->service->updateScripts($data, $scripts);

            // ASSERT
            expect($result['updated'])->toBeTrue()
                ->and($result['added'])->toBe(['test' => ['phpunit']])
                ->and($data['scripts']['test'])->toBe(['phpunit']);
        });

        it('deduplicates commands with normalized comparison', function (): void {
            // ARRANGE
            $data = ['scripts' => ['test' => ['phpunit  --verbose']]];
            $scripts = ['test' => ['phpunit --verbose']];

            // ACT
            $result = $this->service->updateScripts($data, $scripts);

            // ASSERT
            expect($result['updated'])->toBeFalse()
                ->and($result['added'])->toBeEmpty();
        });

        it('converts string script to array when merging', function (): void {
            // ARRANGE
            $data = ['scripts' => ['test' => 'phpunit']];
            $scripts = ['test' => ['pest']];

            // ACT
            $result = $this->service->updateScripts($data, $scripts);

            // ASSERT
            expect($result['updated'])->toBeTrue()
                ->and($data['scripts']['test'])->toBe(['phpunit', 'pest']);
        });

        it('adds new commands to existing script array', function (): void {
            // ARRANGE
            $data = ['scripts' => ['test' => ['phpunit']]];
            $scripts = ['test' => ['pest', 'phpstan']];

            // ACT
            $result = $this->service->updateScripts($data, $scripts);

            // ASSERT
            expect($result['updated'])->toBeTrue()
                ->and($result['added'])->toBe(['test' => ['pest', 'phpstan']])
                ->and($data['scripts']['test'])->toBe(['phpunit', 'pest', 'phpstan']);
        });
    });

    //
    // updateRepositories() tests
    // ----

    describe('updateRepositories()', function (): void {
        it('adds new repository to empty section', function (): void {
            // ARRANGE
            $data = [];
            $repositories = [['type' => 'vcs', 'url' => 'https://github.com/foo/bar']];

            // ACT
            $result = $this->service->updateRepositories($data, $repositories);

            // ASSERT
            expect($result['updated'])->toBeTrue()
                ->and($result['added'])->toBe(['https://github.com/foo/bar'])
                ->and($data['repositories'])->toHaveCount(1);
        });

        it('deduplicates repositories by URL', function (): void {
            // ARRANGE
            $data = ['repositories' => [['type' => 'vcs', 'url' => 'https://github.com/foo/bar']]];
            $repositories = [['type' => 'vcs', 'url' => 'https://github.com/foo/bar']];

            // ACT
            $result = $this->service->updateRepositories($data, $repositories);

            // ASSERT
            expect($result['updated'])->toBeFalse()
                ->and($result['added'])->toBeEmpty()
                ->and($data['repositories'])->toHaveCount(1);
        });

        it('skips repositories without URL', function (): void {
            // ARRANGE
            $data = [];
            $repositories = [['type' => 'vcs']];

            // ACT
            $result = $this->service->updateRepositories($data, $repositories);

            // ASSERT
            expect($result['updated'])->toBeFalse()
                ->and($data['repositories'])->toBeEmpty();
        });
    });

    //
    // updateObjectSection() tests
    // ----

    describe('updateObjectSection()', function (): void {
        it('creates section when not exists', function (): void {
            // ARRANGE
            $data = [];
            $sectionData = ['allow-plugins' => ['foo/bar' => true]];

            // ACT
            $result = $this->service->updateObjectSection($data, 'config', $sectionData);

            // ASSERT
            expect($result)->toBeTrue()
                ->and($data['config'])->toBe(['allow-plugins' => ['foo/bar' => true]]);
        });

        it('recursively merges nested config', function (): void {
            // ARRANGE
            $data = ['config' => ['allow-plugins' => ['existing/plugin' => true]]];
            $sectionData = ['allow-plugins' => ['new/plugin' => true]];

            // ACT
            $result = $this->service->updateObjectSection($data, 'config', $sectionData);

            // ASSERT
            expect($result)->toBeTrue()
                ->and($data['config']['allow-plugins'])->toBe([
                    'existing/plugin' => true,
                    'new/plugin' => true,
                ]);
        });

        it('returns false when no changes made', function (): void {
            // ARRANGE
            $data = ['config' => ['key' => 'value']];
            $sectionData = ['key' => 'value'];

            // ACT
            $result = $this->service->updateObjectSection($data, 'config', $sectionData);

            // ASSERT
            expect($result)->toBeFalse();
        });
    });
});
