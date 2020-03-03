<?php

declare(strict_types=1);

namespace Phpcq\Output;

class BufferedOutput implements OutputInterface
{
    /** @var mixed[][] */
    private $buffer = [];

    /** @var OutputInterface */
    private $output;

    /**
     * BufferedOutput constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function write(string $message, int $verbosity = self::VERBOSITY_NORMAL, int $channel = self::CHANNEL_STDOUT) : void
    {
        $this->buffer[] = [false, [$message, $verbosity, $channel]];
    }

    public function writeln(string $message, int $verbosity = self::VERBOSITY_NORMAL, int $channel = self::CHANNEL_STDOUT) : void
    {
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