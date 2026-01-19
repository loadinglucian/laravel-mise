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

    public function callH2(string $text): void
    {
        $this->h2($text);
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

    public function callNotice(string $message): void
    {
        $this->notice($message);
    }

    public function callUl(string|iterable $lines): void
    {
        $this->ul($lines);
    }

    public function callOl(string|iterable $lines): void
    {
        $this->ol($lines);
    }

    /**
     * @param  array<int|string, mixed>  $details
     */
    public function callDisplayDeets(array $details, bool $ul = false): void
    {
        $this->displayDeets($details, $ul);
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

    it('h2 outputs heading with dynamic underline', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callH2('Subtitle');

        // ASSERT
        $result = $output->fetch();
        $lines = explode("\n", trim($result));
        expect($lines[0])->toBe('▒ ## Subtitle')
            ->and(mb_strlen($lines[1]))->toBe(mb_strlen($lines[0])); // Underline matches heading length
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

    it('notice outputs info symbol prefix', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callNotice('Informational message');

        // ASSERT
        expect($output->fetch())->toBe("▒ ℹ Informational message\n");
    });
});

describe('list formatting', function (): void {
    it('ul adds bullet points to array', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callUl(['Item one', 'Item two', 'Item three']);

        // ASSERT
        expect($output->fetch())->toBe("▒ • Item one\n▒ • Item two\n▒ • Item three\n");
    });

    it('ul handles single string', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callUl('Single item');

        // ASSERT
        expect($output->fetch())->toBe("▒ • Single item\n");
    });

    it('ol adds sequential numbers', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callOl(['First', 'Second', 'Third']);

        // ASSERT
        expect($output->fetch())->toBe("▒ 1. First\n▒ 2. Second\n▒ 3. Third\n");
    });

    it('ol handles single string', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callOl('Only item');

        // ASSERT
        expect($output->fetch())->toBe("▒ 1. Only item\n");
    });
});

describe('displayDeets()', function (): void {
    it('returns early for empty array', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callDisplayDeets([]);

        // ASSERT
        expect($output->fetch())->toBe('');
    });

    it('aligns keys based on longest key', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callDisplayDeets([
            'short' => 'value1',
            'longerkey' => 'value2',
        ]);

        // ASSERT - values are displayed with gray color
        $result = $output->fetch();
        expect($result)->toContain('short:    ')
            ->and($result)->toContain('longerkey:')
            ->and($result)->toContain('value1')
            ->and($result)->toContain('value2')
            ->and($result)->toContain("\033[90m"); // ANSI gray for values
    });

    it('handles nested arrays recursively', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callDisplayDeets([
            'parent' => [
                'child1' => 'nested1',
                'child2' => 'nested2',
            ],
        ]);

        // ASSERT - nested items have bullet prefix and gray values
        $result = $output->fetch();
        expect($result)->toContain('▒ parent:')
            ->and($result)->toContain('• child1:')
            ->and($result)->toContain('nested1')
            ->and($result)->toContain('• child2:')
            ->and($result)->toContain('nested2');
    });

    it('adds bullet prefix when ul flag is true', function (): void {
        // ARRANGE
        [$command, $output] = createCommandWithOutput();

        // ACT
        $command->callDisplayDeets(['key' => 'value'], true);

        // ASSERT
        expect($output->fetch())->toContain('▒ • key:');
    });
});
