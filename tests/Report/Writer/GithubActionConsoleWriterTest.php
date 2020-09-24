<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Writer;

use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\FileRangeBuffer;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Writer\GithubActionConsoleWriter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Phpcq\Report\Writer\GithubActionConsoleWriter
 */
final class GithubActionConsoleWriterTest extends TestCase
{
    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    public function writeErrorsProvider(): array
    {
        return [
            'writes simple error' => [
                'expected' => '::error ::Custom error (reported by task-name)',
                'tool' => 'task-name',
                'diagnostic' => new DiagnosticBuffer(
                    TaskReportInterface::SEVERITY_MAJOR,
                    'Custom error',
                    null,
                    null,
                    null,
                    null,
                    null
                )
            ],
            'writes error with source' => [
                'expected' => '::error ::Custom error (reported by task-name: Codestyle.rule)',
                'tool' => 'task-name',
                'diagnostic' => new DiagnosticBuffer(
                    TaskReportInterface::SEVERITY_MAJOR,
                    'Custom error',
                    'Codestyle.rule',
                    null,
                    null,
                    null,
                    null
                )
            ],
            'writes error with external url' => [
                'expected' => '::error ::Custom error (reported by task-name: Codestyle.rule, see https://example.org)',
                'tool' => 'task-name',
                'diagnostic' => new DiagnosticBuffer(
                    TaskReportInterface::SEVERITY_MAJOR,
                    'Custom error',
                    'Codestyle.rule',
                    null,
                    'https://example.org',
                    null,
                    null
                )
            ],
            'writes error with file path' => [
                'expected' => '::error file=rotten/code/example.php::Custom error (reported by task-name: '
                    . 'Codestyle.rule, see https://example.org)',
                'tool' => 'task-name',
                'diagnostic' => new DiagnosticBuffer(
                    TaskReportInterface::SEVERITY_MAJOR,
                    'Custom error',
                    'Codestyle.rule',
                    [new FileRangeBuffer('rotten/code/example.php', null, null, null, null)],
                    'https://example.org',
                    null,
                    null
                )
            ],
            'writes error with file path and line' => [
                'expected' => '::error file=rotten/code/example.php,line=20::Custom error (reported by task-name: '
                    . 'Codestyle.rule, see https://example.org)',
                'tool' => 'task-name',
                'diagnostic' => new DiagnosticBuffer(
                    TaskReportInterface::SEVERITY_MAJOR,
                    'Custom error',
                    'Codestyle.rule',
                    [new FileRangeBuffer('rotten/code/example.php', 20, null, null, null)],
                    'https://example.org',
                    null,
                    null
                )
            ],
            'writes error with file path, line and column' => [
                'expected' => '::error file=rotten/code/example.php,line=20,col=108::Custom error (reported by '
                    . 'task-name: Codestyle.rule, see https://example.org)',
                'tool' => 'task-name',
                'diagnostic' => new DiagnosticBuffer(
                    TaskReportInterface::SEVERITY_MAJOR,
                    'Custom error',
                    'Codestyle.rule',
                    [new FileRangeBuffer('rotten/code/example.php', 20, 108, null, null)],
                    'https://example.org',
                    null,
                    null
                )
            ],
            'writes error with range prefix' => [
                'expected' => '::error file=rotten/code/example.php,line=20,col=108::[20:108 - 70:2] Custom error '
                    . '(reported by task-name: Codestyle.rule, see https://example.org)',
                'tool' => 'task-name',
                'diagnostic' => new DiagnosticBuffer(
                    TaskReportInterface::SEVERITY_MAJOR,
                    'Custom error',
                    'Codestyle.rule',
                    [new FileRangeBuffer('rotten/code/example.php', 20, 108, 70, 2)],
                    'https://example.org',
                    null,
                    null
                )
            ],
            'writes simple warning' => [
                'expected' => '::warning ::Custom warning (reported by task-name)',
                'tool' => 'task-name',
                'diagnostic' => new DiagnosticBuffer(
                    TaskReportInterface::SEVERITY_MINOR,
                    'Custom warning',
                    null,
                    null,
                    null,
                    null,
                    null
                )
            ],
            'writes warning with source' => [
                'expected' => '::warning ::Custom warning (reported by task-name: Codestyle.rule)',
                'tool' => 'task-name',
                'diagnostic' => new DiagnosticBuffer(
                    TaskReportInterface::SEVERITY_MINOR,
                    'Custom warning',
                    'Codestyle.rule',
                    null,
                    null,
                    null,
                    null
                )
            ],
            'writes warning with external url' => [
                'expected' => '::warning ::Custom warning (reported by task-name: Codestyle.rule, see '
                    . 'https://example.org)',
                'tool' => 'task-name',
                'diagnostic' => new DiagnosticBuffer(
                    TaskReportInterface::SEVERITY_MINOR,
                    'Custom warning',
                    'Codestyle.rule',
                    null,
                    'https://example.org',
                    null,
                    null
                )
            ],
            'writes warning with file path' => [
                'expected' => '::warning file=rotten/code/example.php::Custom warning (reported by task-name: '
                    . 'Codestyle.rule, see https://example.org)',
                'tool' => 'task-name',
                'diagnostic' => new DiagnosticBuffer(
                    TaskReportInterface::SEVERITY_MINOR,
                    'Custom warning',
                    'Codestyle.rule',
                    [new FileRangeBuffer('rotten/code/example.php', null, null, null, null)],
                    'https://example.org',
                    null,
                    null
                )
            ],
            'writes warning with file path and line' => [
                'expected' => '::warning file=rotten/code/example.php,line=20::Custom warning (reported by task-name: '
                    . 'Codestyle.rule, see https://example.org)',
                'tool' => 'task-name',
                'diagnostic' => new DiagnosticBuffer(
                    TaskReportInterface::SEVERITY_MINOR,
                    'Custom warning',
                    'Codestyle.rule',
                    [new FileRangeBuffer('rotten/code/example.php', 20, null, null, null)],
                    'https://example.org',
                    null,
                    null
                )
            ],
            'writes warning with file path, line and column' => [
                'expected' => '::warning file=rotten/code/example.php,line=20,col=108::Custom warning (reported by '
                    . 'task-name: Codestyle.rule, see https://example.org)',
                'tool' => 'task-name',
                'diagnostic' => new DiagnosticBuffer(
                    TaskReportInterface::SEVERITY_MINOR,
                    'Custom warning',
                    'Codestyle.rule',
                    [new FileRangeBuffer('rotten/code/example.php', 20, 108, null, null)],
                    'https://example.org',
                    null,
                    null
                )
            ],
            'writes warning with range prefix' => [
                'expected' => '::warning file=rotten/code/example.php,line=20,col=108::[20:108 - 70:2] Custom warning '
                    . '(reported by task-name: Codestyle.rule, see https://example.org)',
                'tool' => 'task-name',
                'diagnostic' => new DiagnosticBuffer(
                    TaskReportInterface::SEVERITY_MINOR,
                    'Custom warning',
                    'Codestyle.rule',
                    [new FileRangeBuffer('rotten/code/example.php', 20, 108, 70, 2)],
                    'https://example.org',
                    null,
                    null
                )
            ]
        ];
    }

    /**
     * @dataProvider writeErrorsProvider
     */
    public function testWriteErrors(string $expected, string $task, DiagnosticBuffer $diagnostic): void
    {
        $output = $this->getMockForAbstractClass(OutputInterface::class);
        $output
            ->expects($this->once())
            ->method('writeln')
            ->with($expected);

        $report = new ReportBuffer();
        $toolReport = $report->createTaskReport($task);
        $toolReport->addDiagnostic($diagnostic);

        $instance = new GithubActionConsoleWriter($output, $report);
        $instance->write();
    }

    public function ignoredSeveritiesProvider(): array
    {
        return [
            'ignores info severity' => [TaskReportInterface::SEVERITY_INFO],
            'ignores notice severity' => [TaskReportInterface::SEVERITY_MARGINAL],
        ];
    }

    /**
     * @dataProvider ignoredSeveritiesProvider
     */
    public function testIgnoresMessagesWithLowerSeverityThanWarning(string $severity): void
    {
        $output = $this->getMockForAbstractClass(OutputInterface::class);
        $output
            ->expects($this->never())
            ->method('writeln');

        $report = new ReportBuffer();
        $toolReport = $report->createTaskReport('task-name');
        $toolReport->addDiagnostic(new DiagnosticBuffer($severity, 'Message', null, null, null, null, null));

        $instance = new GithubActionConsoleWriter($output, $report);
        $instance->write();
    }
}
