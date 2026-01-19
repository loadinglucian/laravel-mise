<?php

//
// ProcessService Unit Tests - Command execution and output processing
// ----

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use LaravelMise\Services\ProcessService;

describe('ProcessService', function (): void {
    beforeEach(function (): void {
        $this->service = new ProcessService;

        // Access private stripOutputPrefix method via reflection
        $reflection = new ReflectionClass($this->service);
        $this->stripMethod = $reflection->getMethod('stripOutputPrefix');
    });

    //
    // stripOutputPrefix() tests
    // ----

    describe('stripOutputPrefix()', function (): void {
        describe('basic prefix stripping', function (): void {
            it('strips ▒ prefix with space from output', function (): void {
                // ACT & ASSERT
                expect($this->stripMethod->invoke($this->service, '▒ text'))
                    ->toBe('text');
            });

            it('strips ▒ prefix without trailing space', function (): void {
                // ACT & ASSERT
                expect($this->stripMethod->invoke($this->service, '▒text'))
                    ->toBe('text');
            });

            it('converts prefix-only line to empty string', function (): void {
                // ACT & ASSERT
                expect($this->stripMethod->invoke($this->service, '▒ '))
                    ->toBe('');
            });

            it('only strips first prefix when nested', function (): void {
                // ACT & ASSERT
                expect($this->stripMethod->invoke($this->service, '▒ ▒ nested'))
                    ->toBe('▒ nested');
            });
        });

        describe('passthrough behavior', function (): void {
            it('preserves lines without prefix', function (): void {
                // ACT & ASSERT
                expect($this->stripMethod->invoke($this->service, 'normal text'))
                    ->toBe('normal text');
            });

            it('preserves empty strings', function (): void {
                // ACT & ASSERT
                expect($this->stripMethod->invoke($this->service, ''))
                    ->toBe('');
            });

            it('preserves prefix when not at start of line', function (): void {
                // ACT & ASSERT
                expect($this->stripMethod->invoke($this->service, '  ▒ indented'))
                    ->toBe('  ▒ indented');
            });
        });

        describe('ANSI color preservation', function (): void {
            it('preserves ANSI codes before and after prefix', function (): void {
                // ACT & ASSERT
                expect($this->stripMethod->invoke($this->service, "\e[36m▒ \e[0mtext"))
                    ->toBe("\e[36m\e[0mtext");
            });

            it('preserves wrapping ANSI codes', function (): void {
                // ACT & ASSERT
                expect($this->stripMethod->invoke($this->service, "\e[36m▒ colored text\e[0m"))
                    ->toBe("\e[36mcolored text\e[0m");
            });

            it('handles complex ANSI codes with multiple parameters', function (): void {
                // ACT & ASSERT
                expect($this->stripMethod->invoke($this->service, "\e[1;32m▒ bold green"))
                    ->toBe("\e[1;32mbold green");
            });

            it('preserves ANSI codes in lines without prefix', function (): void {
                // ACT & ASSERT
                expect($this->stripMethod->invoke($this->service, "\e[31mred text\e[0m"))
                    ->toBe("\e[31mred text\e[0m");
            });
        });
    });

    //
    // run() tests
    // ----

    describe('run()', function (): void {
        it('returns successful process result', function (): void {
            // ARRANGE
            Process::fake(['*' => Process::result('output', '', 0)]);

            // ACT
            $result = $this->service->run(['echo', 'test']);

            // ASSERT
            expect($result->successful())->toBeTrue();
        });

        it('returns failed process result', function (): void {
            // ARRANGE
            Process::fake(['*' => Process::result('', 'error', 1)]);

            // ACT
            $result = $this->service->run(['failing', 'command']);

            // ASSERT
            expect($result->failed())->toBeTrue();
        });

        it('returns process output', function (): void {
            // ARRANGE
            Process::fake(['*' => Process::result('expected output', '', 0)]);

            // ACT
            $result = $this->service->run(['echo', 'test']);

            // ASSERT
            expect(trim((string) $result->output()))->toBe('expected output');
        });
    });

    //
    // runWithOutput() tests
    // ----

    describe('runWithOutput()', function (): void {
        it('returns successful process result', function (): void {
            // ARRANGE
            Process::fake(['*' => Process::result('output', '', 0)]);

            // ACT
            $result = $this->service->runWithOutput(['echo', 'test'], fn () => null);

            // ASSERT
            expect($result->successful())->toBeTrue();
        });

        it('returns failed process result', function (): void {
            // ARRANGE
            Process::fake(['*' => Process::result('', 'error', 1)]);

            // ACT
            $result = $this->service->runWithOutput(['failing', 'command'], fn () => null);

            // ASSERT
            expect($result->failed())->toBeTrue();
        });

        //
        // Callback behavior is tested indirectly via stripOutputPrefix() tests.
        // Process::fake() doesn't invoke output callbacks - it returns results directly.
    });
});
