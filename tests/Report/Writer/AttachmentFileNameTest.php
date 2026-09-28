<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Report\Writer;

use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Report\Buffer\AttachmentBuffer;
use Phpcq\Runner\Report\Buffer\DiffBuffer;
use Phpcq\Runner\Report\Buffer\ReportBuffer;
use Phpcq\Runner\Report\Report;
use Phpcq\Runner\Report\TaskKind;
use Phpcq\Runner\Report\Writer\FileReportWriter;
use Phpcq\Runner\Test\TemporaryFileProducingTestTrait;
use PHPUnit\Framework\TestCase;

use function tempnam;
use function uniqid;

/** @covers \Phpcq\Runner\Report\Writer\AbstractReportWriter */
final class AttachmentFileNameTest extends TestCase
{
    use TemporaryFileProducingTestTrait;

    public function testMarksFilesOfFixTasks(): void
    {
        $report = new ReportBuffer();
        $this->addTaskReport($report, TaskKind::Fix);
        $this->addTaskReport($report, TaskKind::Diagnostic);
        $report->complete(Report::STATUS_PASSED);

        $targetPath = self::$tempdir . '/' . uniqid('phpcq', true);
        FileReportWriter::writeReport($targetPath, $report);

        self::assertFileExists($targetPath . '/rector-fix-output.log');
        self::assertFileExists($targetPath . '/rector-fix-src.diff');
        self::assertFileExists($targetPath . '/rector-output.log');
        self::assertFileExists($targetPath . '/rector-src.diff');
    }

    private function addTaskReport(ReportBuffer $report, TaskKind $kind): void
    {
        $taskReport = $report->createTaskReport('rector', [], $kind);
        $taskReport->setStatus(TaskReportInterface::STATUS_PASSED);
        $taskReport->addAttachment(new AttachmentBuffer(tempnam(self::$tempdir, ''), 'output.log', null));
        $taskReport->addDiff(new DiffBuffer(tempnam(self::$tempdir, ''), 'src.diff'));
    }
}
