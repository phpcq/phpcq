<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Writer;

use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\FileRangeBuffer;
use Phpcq\Report\Buffer\ToolReportBuffer;
use Phpcq\Report\Writer\DiagnosticIteratorEntry;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Report\Writer\DiagnosticIteratorEntry */
class DiagnosticIteratorEntryTest extends TestCase
{
    public function testCanBeInstantiatedWithoutFileRange(): void
    {
        $entry = new DiagnosticIteratorEntry(
            $tool = new ToolReportBuffer('tool-name', 'report-name', '1.0.0'),
            $diagnostic = new DiagnosticBuffer('error', 'message', null, null, null, null, null),
            null
        );
        $this->assertSame($tool, $entry->getTool());
        $this->assertSame($diagnostic, $entry->getDiagnostic());
        $this->assertNull($entry->getRange());
        $this->assertFalse($entry->isFileRelated());
        $this->assertNull($entry->getFileName());
    }

    public function testCanBeInstantiatedWithFileRange(): void
    {
        $entry = new DiagnosticIteratorEntry(
            $tool = new ToolReportBuffer('tool-name', 'report-name', '1.0.0'),
            $diagnostic = new DiagnosticBuffer('error', 'message', null, null, null, null, null),
            $range = new FileRangeBuffer('some/file', null, null, null, null)
        );
        $this->assertSame($tool, $entry->getTool());
        $this->assertSame($diagnostic, $entry->getDiagnostic());
        $this->assertSame($range, $entry->getRange());
        $this->assertTrue($entry->isFileRelated());
        $this->assertSame('some/file', $entry->getFileName());
    }
}
