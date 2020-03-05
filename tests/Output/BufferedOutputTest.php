<?php

declare(strict_types=1);

namespace Output;

use Phpcq\Output\BufferedOutput;
use Phpcq\Output\OutputInterface;
use PHPUnit\Framework\TestCase;

class BufferedOutputTest extends TestCase
{
    public function testWriteOnRelease(): void
    {
        $mock = $this->createMock(OutputInterface::class);
        $mock
            ->expects($this->never())
            ->method('write');

        $bufferedOutput = new BufferedOutput($mock);
        $bufferedOutput->write('Test');

        $mock = $this->createMock(OutputInterface::class);
        $mock
            ->expects($this->once())
            ->method('write');

        $bufferedOutput = new BufferedOutput($mock);
        $bufferedOutput->write('Test');
        $bufferedOutput->release();
    }

    public function testWritelnOnRelease(): void
    {
        $mock = $this->createMock(OutputInterface::class);
        $mock
            ->expects($this->never())
            ->method('writeln');

        $bufferedOutput = new BufferedOutput($mock);
        $bufferedOutput->write('Test');

        $mock = $this->createMock(OutputInterface::class);
        $mock
            ->expects($this->once())
            ->method('writeln');

        $bufferedOutput = new BufferedOutput($mock);
        $bufferedOutput->writeln('Test');
        $bufferedOutput->release();
    }
}