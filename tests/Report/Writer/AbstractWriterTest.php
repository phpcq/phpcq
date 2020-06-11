<?php

declare(strict_types=1);

namespace Report\Writer;

use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Buffer\AttachmentBuffer;
use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\DiffBuffer;
use Phpcq\Report\Buffer\FileRangeBuffer;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Report;
use PHPUnit\Framework\TestCase;

use function sys_get_temp_dir;
use function tempnam;

abstract class AbstractWriterTest extends TestCase
{
    protected function createFullFeaturedReport(): ReportBuffer
    {
        $report = new ReportBuffer();
        $toolReport = $report->createToolReport('tool', '1.0.0');
        $toolReport->setStatus(ToolReportInterface::STATUS_PASSED);
        $toolReport->addAttachment(new AttachmentBuffer(tempnam(sys_get_temp_dir(), ''), 'foo.xml', 'application/xml'));
        $toolReport->addAttachment(new AttachmentBuffer(tempnam(sys_get_temp_dir(), ''), 'bar.xml', null));
        $report->complete(Report::STATUS_PASSED);

        $report->createToolReport('tool2', '2.0.0')->setStatus(ToolReportInterface::STATUS_FAILED);

        $toolReport->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_INFO, 'Foo bar', 'baz', [], null, null, null)
        );
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
                ],
                'https://example.org/super-helpful-tip',
                ['Some\Class\Name', 'Another\Class\Name'],
                ['category1', 'category2']
            )
        );
        $toolReport->addDiff(new DiffBuffer(tempnam(sys_get_temp_dir(), ''), 'diff1.diff'));

        return $report;
    }
}
