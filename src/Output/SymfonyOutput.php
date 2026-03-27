<?php

declare(strict_types=1);

namespace Phpcq\Runner\Output;

use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Symfony\Component\Console\Output\OutputInterface as SymfonyOutputInterface;

final readonly class SymfonyOutput implements OutputInterface
{
    /**
     * SymfonyOutput constructor.
     *
     * @param SymfonyOutputInterface $output
     */
    public function __construct(private SymfonyOutputInterface $output)
    {
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    #[\Override]
    public function write(
        string $message,
        int $verbosity = self::VERBOSITY_NORMAL,
        int $channel = self::CHANNEL_STDOUT
    ): void {
        $this->output->write($message, false, $verbosity);
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    #[\Override]
    public function writeln(
        string $message,
        int $verbosity = self::VERBOSITY_NORMAL,
        int $channel = self::CHANNEL_STDOUT
    ): void {
        $this->output->writeln($message, $verbosity);
    }
}
