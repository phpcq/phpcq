<?php

declare(strict_types=1);

namespace Phpcq\Runner\Output;

use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class SymfonyConsoleOutput implements OutputInterface
{
    /**
     * @param ConsoleOutputInterface $output
     */
    public function __construct(private readonly ConsoleOutputInterface $output)
    {
    }

    #[\Override]
    public function write(
        string $message,
        int $verbosity = self::VERBOSITY_NORMAL,
        int $channel = self::CHANNEL_STDOUT
    ): void {
        $this->output($message, false, $verbosity, $channel);
    }

    #[\Override]
    public function writeln(
        string $message,
        int $verbosity = self::VERBOSITY_NORMAL,
        int $channel = self::CHANNEL_STDOUT
    ): void {
        $this->output($message, true, $verbosity, $channel);
    }

    private function output(string $output, bool $newLine, int $verbosity, int $channel): void
    {
        match ($channel) {
            self::CHANNEL_STDERR => $this->output->getErrorOutput()->write($output, $newLine, $verbosity),
            default => $this->output->write($output, $newLine, $verbosity),
        };
    }
}
