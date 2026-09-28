<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Report\Writer;

use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Report\Buffer\ReportBuffer;
use Phpcq\Runner\Report\TaskKind;
use Phpcq\Runner\Report\Writer\ConsoleWriter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

use function preg_match;

/**
 * @covers \Phpcq\Runner\Report\Writer\ConsoleWriter
 */
final class ConsoleWriterTest extends TestCase
{
    public function testSummaryIsNotGroupedWithoutFixTasks(): void
    {
        $report = new ReportBuffer();
        $this->addTaskReport($report, 'phpcs', 'phpcs', TaskKind::Diagnostic);

        $output = $this->writeReport($report);

        self::assertStringContainsString('phpcs   phpcs', $output);
        self::assertStringNotContainsString('Fixes', $output);
        self::assertStringNotContainsString('Diagnostics', $output);
    }

    public function testSummaryGroupsFixTasksBeforeDiagnostics(): void
    {
        $report = new ReportBuffer();
        $this->addTaskReport($report, 'phpcs', 'phpcs', TaskKind::Diagnostic);
        $this->addTaskReport($report, 'phpcs', 'phpcbf', TaskKind::Fix);

        $output = $this->writeReport($report);

        self::assertSame(
            1,
            preg_match('#Fixes.*phpcs\s+phpcbf.*Diagnostics.*phpcs\s+phpcs\s#s', $output),
            $output
        );
    }

    private function addTaskReport(ReportBuffer $report, string $taskName, string $toolName, TaskKind $kind): void
    {
        $report
            ->createTaskReport($taskName, ['tool_name' => $toolName, 'tool_version' => '1.0.0'], $kind)
            ->setStatus(TaskReportInterface::STATUS_PASSED);
    }

    private function writeReport(ReportBuffer $report): string
    {
        $report->complete(ReportInterface::STATUS_PASSED);
        $output = new BufferedOutput();
        ConsoleWriter::writeReport(
            $output,
            new SymfonyStyle(new ArrayInput([]), $output),
            $report,
            TaskReportInterface::SEVERITY_NONE
        );

        return $output->fetch();
    }
}
