<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Writer;

use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\FileRangeBuffer;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Writer\DiagnosticIterator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Report\Writer\DiagnosticIterator
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DiagnosticIteratorTest extends TestCase
{
    public function iterateEmptyProvider(): array
    {
        $report = new ReportBuffer();
        return [
            'file/range' => [DiagnosticIterator::sortByFileAndRange($report)],
            'tool' => [DiagnosticIterator::sortByTool($report)],
            '1. file/range, 2. tool' => [DiagnosticIterator::sortByFileAndRange($report)->thenSortByTool()],
            '1. tool, 2. file/range' => [DiagnosticIterator::sortByTool($report)->thenSortByFileAndRange()],
        ];
    }

    /** @dataProvider iterateEmptyProvider */
    public function testIteratesEmpty(DiagnosticIterator $iterator): void
    {
        $this->assertEmpty(iterator_to_array($iterator));
    }

    public function iterateSingleItemProvider(): array
    {
        $report = new ReportBuffer();
        $report->createToolReport('tool')->addDiagnostic($this->diagnostic('error', 'test'));
        return [
            'file/range' => [DiagnosticIterator::sortByFileAndRange($report)],
            'tool' => [DiagnosticIterator::sortByTool($report)],
            '1. file/range, 2. tool' => [DiagnosticIterator::sortByFileAndRange($report)->thenSortByTool()],
            '1. tool, 2. file/range' => [DiagnosticIterator::sortByTool($report)->thenSortByFileAndRange()],
        ];
    }

    /** @dataProvider iterateSingleItemProvider */
    public function testIteratesSingleItem(DiagnosticIterator $iterator): void
    {
        $this->assertCount(1, iterator_to_array($iterator));
    }

    public function testIterateByTool(): void
    {
        $iterator = DiagnosticIterator::sortByTool($this->createReport());

        $elements = [];
        foreach ($iterator as $element) {
            $elements[] = $element->getDiagnostic()->getMessage();
        }

        $this->assertSame(
            [
                'tool1.error1.no-file',
                'tool1.info1.info',
                'tool1.error2.no-file',
                'tool1.error3.file1',
                'tool1.error3.file2-2.1-3.1',
                'tool2.error1.no-file',
                'tool2.error2.no-file',
                'tool2.error3.file1',
                'tool2.error3.file2-1.1-2.1',
            ],
            $elements
        );
    }

    public function testFilterByMinimumSeverityIterateByTool(): void
    {
        $iterator = DiagnosticIterator::filterByMinimumSeverity(
            $this->createReport(),
            ToolReportInterface::SEVERITY_ERROR
        )->thenSortByTool();

        $elements = [];
        foreach ($iterator as $element) {
            $elements[] = $element->getDiagnostic()->getMessage();
        }

        $this->assertSame(
            [
                'tool1.error1.no-file',
                'tool1.error2.no-file',
                'tool1.error3.file1',
                'tool1.error3.file2-2.1-3.1',
                'tool2.error1.no-file',
                'tool2.error2.no-file',
                'tool2.error3.file1',
                'tool2.error3.file2-1.1-2.1',
            ],
            $elements
        );
    }

    public function testIterateByFileAndRangeThenTool(): void
    {
        $iterator = DiagnosticIterator::sortByFileAndRange($this->createReport())->thenSortByTool();

        $elements = [];
        foreach ($iterator as $element) {
            $elements[] = $element->getDiagnostic()->getMessage();
        }

        $this->assertSame(
            [
                'tool1.error1.no-file',
                'tool1.info1.info',
                'tool1.error2.no-file',
                'tool2.error1.no-file',
                'tool2.error2.no-file',
                'tool1.error3.file1',
                'tool2.error3.file1',
                'tool2.error3.file2-1.1-2.1',
                'tool1.error3.file2-2.1-3.1',
            ],
            $elements
        );
    }

    public function testFilterByMinimumSeverityIterateByFileAndRangeThenTool(): void
    {
        $iterator = DiagnosticIterator::filterByMinimumSeverity(
            $this->createReport(),
            ToolReportInterface::SEVERITY_ERROR
        )->thenSortByFileAndRange()->thenSortByTool();

        $elements = [];
        foreach ($iterator as $element) {
            $elements[] = $element->getDiagnostic()->getMessage();
        }

        $this->assertSame(
            [
                'tool1.error1.no-file',
                'tool1.error2.no-file',
                'tool2.error1.no-file',
                'tool2.error2.no-file',
                'tool1.error3.file1',
                'tool2.error3.file1',
                'tool2.error3.file2-1.1-2.1',
                'tool1.error3.file2-2.1-3.1',
            ],
            $elements
        );
    }

    public function testFilterByMinimumSeverityIterateByToolThenByFileAndRange(): void
    {
        $iterator = DiagnosticIterator::filterByMinimumSeverity(
            $this->createReport(),
            ToolReportInterface::SEVERITY_ERROR
        )->thenSortByTool()->thenSortByFileAndRange();

        $elements = [];
        foreach ($iterator as $element) {
            $elements[] = $element->getDiagnostic()->getMessage();
        }

        $this->assertSame(
            [
                'tool1.error1.no-file',
                'tool1.error2.no-file',
                'tool1.error3.file1',
                'tool1.error3.file2-2.1-3.1',
                'tool2.error1.no-file',
                'tool2.error2.no-file',
                'tool2.error3.file1',
                'tool2.error3.file2-1.1-2.1',
            ],
            $elements
        );
    }

    public function testIterateByToolThenByFileAndRange(): void
    {
        $iterator = DiagnosticIterator::sortByTool($this->createReport())->thenSortByFileAndRange();

        $elements = [];
        foreach ($iterator as $element) {
            $elements[] = $element->getDiagnostic()->getMessage();
        }

        $this->assertSame(
            [
                'tool1.error1.no-file',
                'tool1.info1.info',
                'tool1.error2.no-file',
                'tool1.error3.file1',
                'tool1.error3.file2-2.1-3.1',
                'tool2.error1.no-file',
                'tool2.error2.no-file',
                'tool2.error3.file1',
                'tool2.error3.file2-1.1-2.1',
            ],
            $elements
        );
    }

    public function testIterateUnsortedFilteredByMinimumSeverity(): void
    {
        $iterator = DiagnosticIterator::filterByMinimumSeverity(
            $this->createReport(),
            ToolReportInterface::SEVERITY_ERROR
        );

        $elements = [];
        foreach ($iterator as $element) {
            $elements[] = $element->getDiagnostic()->getMessage();
        }

        $this->assertSame(
            [
                'tool2.error1.no-file',
                'tool2.error2.no-file',
                'tool2.error3.file1',
                'tool2.error3.file2-1.1-2.1',
                'tool1.error1.no-file',
                'tool1.error2.no-file',
                'tool1.error3.file1',
                'tool1.error3.file2-2.1-3.1',
            ],
            $elements
        );
    }

    public function testIterateOverDuplicateFileLists(): void
    {
        $report = new ReportBuffer();
        $tool   = $report->createToolReport('tool');
        $tool->addDiagnostic($this->diagnostic('error', 'file1.74', null, $this->range('file1', 74, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file1.82', null, $this->range('file1', 82, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file1.83', null, $this->range('file1', 83, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file1.73', null, $this->range('file1', 73, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file1.90', null, $this->range('file1', 90, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file2.75', null, $this->range('file2', 75, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file2.91', null, $this->range('file2', 91, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file2.73', null, $this->range('file2', 73, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file1.90', null, $this->range('file1', 90, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file1.100', null, $this->range('file1', 100, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file2.63', null, $this->range('file2', 63, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file2.68', null, $this->range('file2', 68, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file1.101', null, $this->range('file1', 101, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file1.101', null, $this->range('file1', 101, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file1.105', null, $this->range('file1', 105, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file1.109', null, $this->range('file1', 109, 10)));
        $tool->addDiagnostic($this->diagnostic('error', 'file2.71', null, $this->range('file2', 71, 10)));

        $iterator = DiagnosticIterator::sortByTool($report)->thenSortByFileAndRange()->getIterator();
        $elements = [];
        foreach ($iterator as $element) {
            $elements[] = $element->getDiagnostic()->getMessage();
        }

        $this->assertSame(
            [
                'file1.73',
                'file1.74',
                'file1.82',
                'file1.83',
                'file1.90',
                'file1.90',
                'file1.100',
                'file1.101',
                'file1.101',
                'file1.105',
                'file1.109',
                'file2.63',
                'file2.68',
                'file2.71',
                'file2.73',
                'file2.75',
                'file2.91',
            ],
            $elements
        );
    }

    private function diagnostic(
        string $severity,
        string $message,
        ?string $source = null,
        ?FileRangeBuffer $fileRange = null
    ): DiagnosticBuffer {
        return new DiagnosticBuffer($severity, $message, $source, $fileRange ? [$fileRange] : null);
    }

    private function range(
        string $file,
        ?int $startLine = null,
        ?int $startColumn = null,
        ?int $endLine = null,
        ?int $endColumn = null
    ) {
        return new FileRangeBuffer($file, $startLine, $startColumn, $endLine, $endColumn);
    }

    private function createReport(): ReportBuffer
    {
        $report = new ReportBuffer();

        // Create inverse, to ensure we have some sorting action.
        $tool2 = $report->createToolReport('tool2');
        $tool1 = $report->createToolReport('tool1');

        $tool1->addDiagnostic($this->diagnostic('error', 'tool1.error1.no-file'));
        $tool1->addDiagnostic($this->diagnostic('info', 'tool1.info1.info'));
        $tool2->addDiagnostic($this->diagnostic('error', 'tool2.error1.no-file'));
        $tool1->addDiagnostic($this->diagnostic('error', 'tool1.error2.no-file'));
        $tool2->addDiagnostic($this->diagnostic('error', 'tool2.error2.no-file'));
        $tool1->addDiagnostic($this->diagnostic('error', 'tool1.error3.file1', null, $this->range('file1')));
        $tool2->addDiagnostic($this->diagnostic('error', 'tool2.error3.file1', null, $this->range('file1')));
        $tool1->addDiagnostic(
            $this->diagnostic('error', 'tool1.error3.file2-2.1-3.1', null, $this->range('file2', 2, 2, 3, 1))
        );
        $tool2->addDiagnostic(
            $this->diagnostic('error', 'tool2.error3.file2-1.1-2.1', null, $this->range('file2', 1, 1, 2, 1))
        );

        return $report;
    }
}
