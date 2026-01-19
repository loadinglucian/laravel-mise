<?php

declare(strict_types=1);

use Illuminate\Console\OutputStyle;
use LaravelMise\Commands\BaseCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Concrete command to expose protected BaseCommand methods for testing.
 */
class TestableCommand extends BaseCommand
{
    protected $signature = 'test:command';

    protected $description = 'Test command for BaseCommand testing';

    public function handle(): int
    {
        return self::SUCCESS;
    }

    public function callOut(string|iterable $lines): void
    {
        $this->out($lines);
    }

    public function callHr(): void
    {
        $this->hr();
    }

    public function callH1(string $text): void
    {
        $this->h1($text);
    }

    public function callYay(string $message): void
    {
        $this->yay($message);
    }

    public function callNay(string $message): void
    {
        $this->nay($message);
    }

    public function callWarning(string $message): void
    {
        $this->warning($message);
    }

    public function callBanner(): void
    {
        $this->banner();
    }
}

//
// Test Helpers
// ----

/**
 * Create a TestableCommand with output capture.
 *
 * @return array{0: TestableCommand, 1: BufferedOutput}
 */
function createCommandWithOutput(): array
{
    $command = new TestableCommand;
    // Use decorated mode to preserve color tags in output
    $bufferedOutput = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
    $outputStyle = new OutputStyle(new ArrayInput([]), $bufferedOutput);
    $command->setOutput($outputStyle);

    return [$command, $bufferedOutput];
}

//
// BaseCommand Output Helpers tests
// ----

describe('out()', function (): void {
    it('adds prefix to plain text', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callOut('Hello World');

        // ASSERT
        expect($output->fetch())->toBe("▒ Hello World\n");
    });

    it('preserves leading color tags when adding prefix', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callOut('<fg=red>Error message</>');

        // ASSERT - prefix is inserted after opening color tag
        $result = $output->fetch();
        expect($result)->toContain('▒ Error message')
            ->and($result)->toContain("\033[31m"); // ANSI red
    });

    it('converts color shorthand to fg syntax', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callOut('<|gray>Gray text</>');

        // ASSERT - shorthand <|gray> converted to <fg=gray> then to ANSI
        $result = $output->fetch();
        expect($result)->toContain('▒ Gray text')
            ->and($result)->toContain("\033[90m"); // ANSI gray
    });

    it('handles array of lines', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callOut(['Line one', 'Line two']);

        // ASSERT
        expect($output->fetch())->toBe("▒ Line one\n▒ Line two\n");
    });
});

describe('structural elements', function (): void {
    it('hr outputs 76-character rule', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callHr();

        // ASSERT
        $result = $output->fetch();
        expect($result)->toContain('▒ ')
            ->and(mb_strlen(trim($result)))->toBe(78); // 76 dashes + '▒ ' prefix
    });

    it('h1 outputs heading with horizontal rule', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callH1('Main Title');

        // ASSERT
        $result = $output->fetch();
        expect($result)->toContain("▒ \n")
            ->and($result)->toContain('▒ # Main Title')
            ->and($result)->toContain('────────────────────────');
    });
});

describe('status messages', function (): void {
    it('yay outputs checkmark prefix', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callYay('Success');

        // ASSERT
        expect($output->fetch())->toBe("▒ ✓ Success\n");
    });

    it('nay outputs red X prefix', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callNay('Error occurred');

        // ASSERT
        $result = $output->fetch();
        expect($result)->toContain('▒ ✗ Error occurred')
            ->and($result)->toContain("\033[31m"); // ANSI red
    });

    it('warning outputs exclamation prefix', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callWarning('Be careful');

        // ASSERT
        expect($output->fetch())->toBe("▒ ! Be careful\n");
    });
});

describe('banner()', function (): void {
    it('outputs application name and version', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callBanner();

        // ASSERT
        $result = $output->fetch();
        expect($result)->toContain('Laravel Mise')
            ->and($result)->toContain('Ver:');
    });

    it('outputs decorated header line with gradient colors', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callBanner();

        // ASSERT - check for the decorative header structure
        $result = $output->fetch();
        expect($result)->toContain('▒ ≡')
            ->and($result)->toContain('━');
    });

    it('displays version string from composer or dev fallback', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callBanner();

        // ASSERT - version is either a semver (x.y.z) or 'dev' fallback
        $result = $output->fetch();
        expect($result)->toMatch('/Ver: (dev|\d+\.\d+\.\d+)/');
    });
});
