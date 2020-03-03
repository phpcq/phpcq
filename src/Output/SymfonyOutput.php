<?php

declare(strict_types=1);

namespace Phpcq\Output;

use Symfony\Component\Console\Output\OutputInterface as SymfonyOutputInterface;

final class SymfonyOutput implements OutputInterface
{
    /** @var SymfonyOutputInterface */
    private $output;

    /**
     * SymfonyOutput constructor.
     *
     * @param SymfonyOutputInterface $output
     */
    public function __construct(SymfonyOutputInterface $output)
    {
        $this->output = $output;
    }

    public function write(string $message, int $verbosity = self::VERBOSITY_NORMAL, int $channel = self::CHANNEL_STDOUT) : void
    {
        $this->output->write($message, false, $verbosity);
    }

    public function writeln(string $message, int $verbosity = self::VERBOSITY_NORMAL, int $channel = self::CHANNEL_STDOUT) : void
    {
        $this->output->writeln($message, $verbosity);
    }
}