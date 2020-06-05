<?php

declare(strict_types=1);

namespace Report\Writer;

use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\FileRangeBuffer;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Report;
use PHPUnit\Framework\TestCase;

abstract class AbstractWriterTest extends TestCase
{
    protected function createFullFeaturedReport(): ReportBuffer
    {
        $report = new ReportBuffer();
        $toolReport = $report->createToolReport('tool');
        $toolReport->setStatus(ToolReportInterface::STATUS_PASSED);
        $toolReport->addAttachment(tempnam(sys_get_temp_dir(), ''), 'foo.xml');
        $report->complete(Report::STATUS_PASSED);

        $report->createToolReport('tool2')->setStatus(ToolReportInterface::STATUS_FAILED);

        $toolReport->addDiagnostic(new DiagnosticBuffer(ToolReportInterface::SEVERITY_INFO, 'Foo bar', 'baz', []));
        $toolReport->addDiagnostic(
            new DiagnosticBuffer(
                ToolReportInterface::SEVERITY_ERROR,
                'Failure',
                null,
                [
                    new FileRangeBuffer('example.php', 1, null, null, null),
                    new FileRangeBuffer('example.php', 1, 2, null, null),
                    new FileRangeBuffer('example2.php', 1, 2, 3, null),
                    new FileRangeBuffer('example2.php', 1, 2, 3, 4)
                ]
            )
        );

        return $report;
    }
}
