<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Report\Buffer;

use Phpcq\Runner\Report\Buffer\DiagnosticBuffer;
use Phpcq\Runner\Report\Buffer\FileRangeBuffer;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Report\Buffer\DiagnosticBuffer */
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
            'https://example.org/more-info',
            ['Some\Class\Name', 'Another\Class\Name'],
            ['category1', 'category2']
        );
        $this->assertSame('error', $buffer->getSeverity());
        $this->assertSame('Hello world', $buffer->getMessage());
        $this->assertSame('Tool source name', $buffer->getSource());
        $this->assertTrue($buffer->hasFileRanges());
        $this->assertSame([$range1, $range2], iterator_to_array($buffer->getFileRanges()));
        $this->assertSame('https://example.org/more-info', $buffer->getExternalInfoUrl());
        $this->assertSame(['Some\Class\Name', 'Another\Class\Name'], iterator_to_array($buffer->getClassNames()));
        $this->assertSame(['category1', 'category2'], iterator_to_array($buffer->getCategories()));
    }

    public function testConstructionCreatesWithEmptyRangeArray(): void
    {
        $buffer = new DiagnosticBuffer('error', 'Hello world', null, [], null, null, null);

        $this->assertSame('error', $buffer->getSeverity());
        $this->assertSame('Hello world', $buffer->getMessage());
        $this->assertNull($buffer->getSource());
        $this->assertFalse($buffer->hasFileRanges());
        $this->assertSame([], iterator_to_array($buffer->getFileRanges()));
        $this->assertNull($buffer->getExternalInfoUrl());
    }

    public function testConstructionCreatesWithNullValues(): void
    {
        $buffer = new DiagnosticBuffer('error', 'Hello world', null, null, null, null, null);

        $this->assertSame('error', $buffer->getSeverity());
        $this->assertSame('Hello world', $buffer->getMessage());
        $this->assertNull($buffer->getSource());
        $this->assertFalse($buffer->hasFileRanges());
        $this->assertSame([], iterator_to_array($buffer->getFileRanges()));
        $this->assertNull($buffer->getExternalInfoUrl());
    }
}
