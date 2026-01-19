<?php

//
// ProcessService Unit Tests - Command execution with TTY/callback support
// ----

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use LaravelMise\Services\ProcessService;

//
// Test double that forces TTY mode for testing
// ----

readonly class TtyEnabledProcessService extends ProcessService
{
    #[\Override]
    protected function shouldUseTty(?callable $outputCallback): bool
    {
        // Force TTY mode regardless of environment
        return $outputCallback === null;
    }
}

//
// Test double that captures TTY decision for verification
// ----

class TtyDecisionCapture
{
    public bool $lastTtyDecision = false;
}

readonly class TestableProcessService extends ProcessService
{
    public function __construct(
        private TtyDecisionCapture $capture = new TtyDecisionCapture,
    ) {}

    #[\Override]
    protected function shouldUseTty(?callable $outputCallback): bool
    {
        $this->capture->lastTtyDecision = parent::shouldUseTty($outputCallback);

        return $this->capture->lastTtyDecision;
    }

    public function getLastTtyDecision(): bool
    {
        return $this->capture->lastTtyDecision;
    }
}

describe('ProcessService', function (): void {
    beforeEach(function (): void {
        $this->service = new ProcessService;
    });

    //
    // shouldUseTty() behavior tests
    // ----

    describe('shouldUseTty() behavior', function (): void {
        it('disables TTY when callback is provided', function (): void {
            // ARRANGE
            Process::fake(['*' => Process::result('output', '', 0)]);
            $testable = new TestableProcessService;

            // ACT
            $testable->run(['echo', 'test'], fn () => null);

            // ASSERT - callback provided means no TTY
            expect($testable->getLastTtyDecision())->toBeFalse();
        });

        it('disables TTY during unit tests (even with null callback)', function (): void {
            // ARRANGE
            Process::fake(['*' => Process::result('output', '', 0)]);
            $testable = new TestableProcessService;

            // ACT
            $testable->run(['echo', 'test'], null);

            // ASSERT - runningUnitTests() returns true, so TTY is disabled
            expect($testable->getLastTtyDecision())->toBeFalse();
        });
    });

    //
    // run() tests
    // ----

    describe('run()', function (): void {
        describe('without callback (callback mode in tests)', function (): void {
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

        describe('with callback (non-TTY mode)', function (): void {
            it('returns successful process result', function (): void {
                // ARRANGE
                Process::fake(['*' => Process::result('output', '', 0)]);

                // ACT
                $result = $this->service->run(['echo', 'test'], fn () => null);

                // ASSERT
                expect($result->successful())->toBeTrue();
            });

            it('returns failed process result', function (): void {
                // ARRANGE
                Process::fake(['*' => Process::result('', 'error', 1)]);

                // ACT
                $result = $this->service->run(['failing', 'command'], fn () => null);

                // ASSERT
                expect($result->failed())->toBeTrue();
            });
        });

        //
        // Note: TTY mode cannot be tested in isolation because:
        // 1. Process::fake() doesn't intercept the .tty() call - it's set before run()
        // 2. Even with a test double, Laravel's Process still validates TTY availability
        // 3. TTY requires /dev/tty which isn't available in CI/test environments
        //
        // The TTY code path is validated by:
        // - shouldUseTty() behavior tests above (verify conditions)
        // - Manual testing in actual terminal environments
        // - The test double TtyEnabledProcessService documents the intended behavior
    });
});
