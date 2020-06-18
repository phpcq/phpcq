<?php

declare(strict_types=1);

namespace Phpcq\Test\Output;

use Phpcq\Output\SymfonyConsoleOutput;
use Phpcq\Output\SymfonyOutput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Phpcq\Output\SymfonyConsoleOutput
 */
class SymfonyConsoleOutputTest extends TestCase
{
    public function testWrite(): void
    {
        $mock = $this->createMock(ConsoleOutputInterface::class);

        $mock
            ->expects($this->once())
            ->method('write')
            ->with('Test', false, ConsoleOutputInterface::VERBOSITY_NORMAL);

        $output = new SymfonyOutput($mock);
        $output->write('Test');
    }

    public function testWriteOnErrorChannel(): void
    {
        $mock = $this->createMock(ConsoleOutputInterface::class);
        $errorOutput = $this->createMock(OutputInterface::class);

        $errorOutput
            ->expects($this->once())
            ->method('write')
            ->with('Test', false, ConsoleOutputInterface::VERBOSITY_NORMAL);

        $mock
            ->expects($this->once())
            ->method('getErrorOutput')
            ->willReturn($errorOutput);

        $output = new SymfonyConsoleOutput($mock);
        $output->write('Test', SymfonyOutput::VERBOSITY_NORMAL, SymfonyOutput::CHANNEL_STDERR);
    }

    public function testWriteln(): void
    {
        $mock = $this->createMock(ConsoleOutputInterface::class);

        $mock
            ->expects($this->once())
            ->method('write')
            ->with('Test', true, ConsoleOutputInterface::VERBOSITY_NORMAL);

        $output = new SymfonyConsoleOutput($mock);
        $output->writeln('Test');
    }


    public function testWritelnOnErrorChannel(): void
    {
        $mock = $this->createMock(ConsoleOutputInterface::class);
        $errorOutput = $this->createMock(OutputInterface::class);

        $errorOutput
            ->expects($this->once())
            ->method('write')
            ->with('Test', true, ConsoleOutputInterface::VERBOSITY_NORMAL);

        $mock
            ->expects($this->once())
            ->method('getErrorOutput')
            ->willReturn($errorOutput);

        $output = new SymfonyConsoleOutput($mock);
        $output->writeln('Test', SymfonyOutput::VERBOSITY_NORMAL, SymfonyOutput::CHANNEL_STDERR);
    }
}
