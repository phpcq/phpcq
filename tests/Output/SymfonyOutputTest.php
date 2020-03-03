<?php

declare(strict_types=1);

namespace Phpcq\Test\Output;

use Phpcq\Output\SymfonyOutput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface as SymfonyOutputInterface;

class SymfonyOutputTest extends TestCase
{
    public function testWrite()
    {
        $mock = $this->createMock(SymfonyOutputInterface::class);

        $mock
            ->expects($this->once())
            ->method('write')
            ->with('Test', false, SymfonyOutputInterface::VERBOSITY_NORMAL);

        $output = new SymfonyOutput($mock);
        $output->write('Test');
    }

    public function testWriteln()
    {
        $mock = $this->createMock(SymfonyOutputInterface::class);

        $mock
            ->expects($this->once())
            ->method('writeln')
            ->with('Test', SymfonyOutputInterface::VERBOSITY_NORMAL);

        $output = new SymfonyOutput($mock);
        $output->writeln('Test');
    }
}