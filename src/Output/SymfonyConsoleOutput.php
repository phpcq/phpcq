<?php

declare(strict_types=1);

namespace Phpcq\Runner\Output;

use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class SymfonyConsoleOutput implements OutputInterface
{
    /** @var ConsoleOutputInterface */
    private $output;

    /**
     * @param ConsoleOutputInterface $output
     */
    public function __construct(ConsoleOutputInterface $output)
    {
        $this->output = $output;
    }

    public function write(
        string $message,
        int $verbosity = self::VERBOSITY_NORMAL,
        int $channel = self::CHANNEL_STDOUT
    ): void {
        $this->output($message, false, $verbosity, $channel);
    }

    public function writeln(
        string $message,
        int $verbosity = self::VERBOSITY_NORMAL,
        int $channel = self::CHANNEL_STDOUT
    ): void {
        $this->output($message, true, $verbosity, $channel);
    }

    private function output(string $output, bool $newLine, int $verbosity, int $channel): void
    {
        switch ($channel) {
            case self::CHANNEL_STDERR:
                $this->output->getErrorOutput()->write($output, $newLine, $verbosity);
                break;

            case self::CHANNEL_STDOUT:
            default:
                $this->output->write($output, $newLine, $verbosity);
        }
    }
}
