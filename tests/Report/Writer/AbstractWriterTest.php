<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Writer;

use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Report\Buffer\AttachmentBuffer;
use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\DiffBuffer;
use Phpcq\Report\Buffer\FileRangeBuffer;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Report;
use Phpcq\Test\TemporaryFileProducingTestTrait;
use PHPUnit\Framework\TestCase;

use function tempnam;

abstract class AbstractWriterTest extends TestCase
{
    use TemporaryFileProducingTestTrait;

    protected function createFullFeaturedReport(): ReportBuffer
    {
        $report = new ReportBuffer();
        $taskReport = $report->createTaskReport('tool');
        $taskReport->setStatus(TaskReportInterface::STATUS_PASSED);
        $taskReport->addAttachment(new AttachmentBuffer(tempnam(self::$tempdir, ''), 'foo.xml', 'application/xml'));
        $taskReport->addAttachment(new AttachmentBuffer(tempnam(self::$tempdir, ''), 'bar.xml', null));
        $report->complete(Report::STATUS_PASSED);

        $report->createTaskReport('tool2')->setStatus(TaskReportInterface::STATUS_FAILED);

        $taskReport->addDiagnostic(
            new DiagnosticBuffer(TaskReportInterface::SEVERITY_INFO, 'Foo bar', 'baz', [], null, null, null)
        );
        $taskReport->addDiagnostic(
            new DiagnosticBuffer(
                TaskReportInterface::SEVERITY_MAJOR,
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
        $taskReport->addDiff(new DiffBuffer(tempnam(self::$tempdir, ''), 'diff1.diff'));

        return $report;
    }
}
