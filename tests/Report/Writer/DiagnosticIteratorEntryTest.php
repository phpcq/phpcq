<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Report\Writer;

use Phpcq\Runner\Report\Buffer\DiagnosticBuffer;
use Phpcq\Runner\Report\Buffer\FileRangeBuffer;
use Phpcq\Runner\Report\Buffer\TaskReportBuffer;
use Phpcq\Runner\Report\Writer\DiagnosticIteratorEntry;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Report\Writer\DiagnosticIteratorEntry */
class DiagnosticIteratorEntryTest extends TestCase
{
    public function testCanBeInstantiatedWithoutFileRange(): void
    {
        $entry = new DiagnosticIteratorEntry(
            $task = new TaskReportBuffer('task-name', 'report-name'),
            $diagnostic = new DiagnosticBuffer('error', 'message', null, null, null, null, null),
            null
        );
        $this->assertSame($task, $entry->getTask());
        $this->assertSame($diagnostic, $entry->getDiagnostic());
        $this->assertNull($entry->getRange());
        $this->assertFalse($entry->isFileRelated());
        $this->assertNull($entry->getFileName());
    }

    public function testCanBeInstantiatedWithFileRange(): void
    {
        $entry = new DiagnosticIteratorEntry(
            $task = new TaskReportBuffer('task-name', 'report-name'),
            $diagnostic = new DiagnosticBuffer('error', 'message', null, null, null, null, null),
            $range = new FileRangeBuffer('some/file', null, null, null, null)
        );
        $this->assertSame($task, $entry->getTask());
        $this->assertSame($diagnostic, $entry->getDiagnostic());
        $this->assertSame($range, $entry->getRange());
        $this->assertTrue($entry->isFileRelated());
        $this->assertSame('some/file', $entry->getFileName());
    }
}
