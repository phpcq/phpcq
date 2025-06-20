<?php

declare(strict_types=1);

namespace Phpcq\Runner\Output;

use Phpcq\PluginApi\Version10\Output\OutputInterface;

/**
 * @psalm-import-type TOutputVerbosity from \Phpcq\PluginApi\Version10\Output\OutputInterface
 * @psalm-import-type TOutputChannel from \Phpcq\PluginApi\Version10\Output\OutputInterface
 */
class BufferedOutput implements OutputInterface
{
    /**
     * @var mixed[][]
     *
     * @var list<array{bool, array{string, TOutputVerbosity, TOutputChannel}}>
     */
    private array $buffer = [];

    /**
     * BufferedOutput constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(private readonly OutputInterface $output)
    {
    }

    #[\Override]
    public function write(
        string $message,
        int $verbosity = self::VERBOSITY_NORMAL,
        int $channel = self::CHANNEL_STDOUT
    ): void {
        $this->buffer[] = [false, [$message, $verbosity, $channel]];
    }

    #[\Override]
    public function writeln(
        string $message,
        int $verbosity = self::VERBOSITY_NORMAL,
        int $channel = self::CHANNEL_STDOUT
    ): void {
        $this->buffer[] = [true, [$message, $verbosity, $channel]];
    }

    public function release(): void
    {
        foreach ($this->buffer as $message) {
            if ($message[0]) {
                $this->output->writeln(...$message[1]);
            } else {
                $this->output->write(...$message[1]);
            }
        }

        $this->buffer = [];
    }
}
