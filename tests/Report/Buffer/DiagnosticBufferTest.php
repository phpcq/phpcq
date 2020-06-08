<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Buffer;

use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\FileRangeBuffer;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Report\Buffer\DiagnosticBuffer */
final class DiagnosticBufferTest extends TestCase
{
    public function testConstructionCreatesWithRanges(): void
    {
        $buffer = new DiagnosticBuffer(
            'error',
            'Hello world',
            'Tool source name',
            [
                $range1 = new FileRangeBuffer('/file/name', null, null, null, null),
                $range2 = new FileRangeBuffer('/another/file/name', null, null, null, null),
            ],
            'https://example.org/more-info'
        );
        $this->assertSame('error', $buffer->getSeverity());
        $this->assertSame('Hello world', $buffer->getMessage());
        $this->assertSame('Tool source name', $buffer->getSource());
        $this->assertTrue($buffer->hasFileRanges());
        $this->assertSame([$range1, $range2], iterator_to_array($buffer->getFileRanges()));
        $this->assertSame('https://example.org/more-info', $buffer->getExternalInfoUrl());
    }

    public function testConstructionCreatesWithEmptyRangeArray(): void
    {
        $buffer = new DiagnosticBuffer('error', 'Hello world', null, [], null);

        $this->assertSame('error', $buffer->getSeverity());
        $this->assertSame('Hello world', $buffer->getMessage());
        $this->assertNull($buffer->getSource());
        $this->assertFalse($buffer->hasFileRanges());
        $this->assertSame([], iterator_to_array($buffer->getFileRanges()));
        $this->assertNull($buffer->getExternalInfoUrl());
    }

    public function testConstructionCreatesWithNullValues(): void
    {
        $buffer = new DiagnosticBuffer('error', 'Hello world', null, null, null);

        $this->assertSame('error', $buffer->getSeverity());
        $this->assertSame('Hello world', $buffer->getMessage());
        $this->assertNull($buffer->getSource());
        $this->assertFalse($buffer->hasFileRanges());
        $this->assertSame([], iterator_to_array($buffer->getFileRanges()));
        $this->assertNull($buffer->getExternalInfoUrl());
    }
}
