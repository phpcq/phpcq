<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Report\Writer;

use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Report\Buffer\DiagnosticBuffer;
use Phpcq\Runner\Report\Buffer\FileRangeBuffer;
use Phpcq\Runner\Report\Buffer\ReportBuffer;
use Phpcq\Runner\Report\Writer\CodeClimateReportWriter;
use Phpcq\Runner\Test\TemporaryFileProducingTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Report\Writer\CodeClimateReportWriter
 */
final class CodeClimateReportWriterTest extends TestCase
{
    use TemporaryFileProducingTestTrait;

    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    public function writeErrorsProvider(): array
    {
        return [
            'writes simple error' => [
                'expected' => [
                    [
                        'type' => 'issue',
                        'check_name' => 'task-name',
                        'description' => 'Custom error',
                        'categories' => [
                            'Bug Risk',
                        ],
                        'location' => [
                            'path' => 'unknown',
                            'lines' => [
                                'begin' => 1,
                                'end' => 1,
                            ],
                        ],
                        'severity' => 'major',
                        'fingerprint' => '814fd4817abd5d8c288464627779adef',
                    ],
                ],
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
                'expected' => [
                    [
                        'type' => 'issue',
                        'check_name' => 'task-name: Codestyle.rule',
                        'description' => 'Custom error',
                        'categories' => [
                            'Bug Risk',
                        ],
                        'location' => [
                            'path' => 'unknown',
                            'lines' => [
                                'begin' => 1,
                                'end' => 1,
                            ],
                        ],
                        'severity' => 'major',
                        'fingerprint' => '7fbbaaabbe040ab6ba6efff4d451dd76',
                    ],
                ],
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
                'expected' => [
                    [
                        'type' => 'issue',
                        'check_name' => 'task-name: Codestyle.rule',
                        'description' => 'Custom error (See: https://example.org)',
                        'categories' => [
                            'Bug Risk',
                        ],
                        'location' => [
                            'path' => 'unknown',
                            'lines' => [
                                'begin' => 1,
                                'end' => 1,
                            ],
                        ],
                        'severity' => 'major',
                        'fingerprint' => '7fbbaaabbe040ab6ba6efff4d451dd76',
                    ],
                ],
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
                'expected' => [
                    [
                        'type' => 'issue',
                        'check_name' => 'task-name: Codestyle.rule',
                        'description' => 'Custom error (See: https://example.org)',
                        'categories' => [
                            'Bug Risk',
                        ],
                        'location' => [
                            'path' => 'rotten/code/example.php',
                            'lines' => [
                                'begin' => 1,
                                'end' => 1,
                            ],
                        ],
                        'severity' => 'major',
                        'fingerprint' => '1ef6558125a33b3461460b29e310e205',
                    ],
                ],
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
                'expected' => [
                    [
                        'type' => 'issue',
                        'check_name' => 'task-name: Codestyle.rule',
                        'description' => 'Custom error (See: https://example.org)',
                        'categories' => [
                            'Bug Risk',
                        ],
                        'location' => [
                            'path' => 'rotten/code/example.php',
                            'lines' => [
                                'begin' => 20,
                                'end' => 20,
                            ],
                        ],
                        'severity' => 'major',
                        'fingerprint' => '9c2f3f01fcc58385821f75fb155beed5',
                    ],
                ],
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
                'expected' => [
                    [
                        'type' => 'issue',
                        'check_name' => 'task-name: Codestyle.rule',
                        'description' => 'Custom error (See: https://example.org)',
                        'categories' => [
                            'Bug Risk',
                        ],
                        'location' => [
                            'path' => 'rotten/code/example.php',
                            'positions' => [
                                'begin' => [
                                    'line' => 20,
                                    'column' => 108,
                                ],
                                'end' => [
                                    'line' => 20,
                                    'column' => 108,
                                ],
                            ],
                        ],
                        'severity' => 'major',
                        'fingerprint' => '38f8e35abc9ba0baacaf40b5ea878877',
                    ],
                ],
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
                'expected' => [
                    [
                        'type' => 'issue',
                        'check_name' => 'task-name: Codestyle.rule',
                        'description' => 'Custom error (See: https://example.org)',
                        'categories' => [
                            'Bug Risk',
                        ],
                        'location' => [
                            'path' => 'rotten/code/example.php',
                            'positions' => [
                                'begin' => [
                                    'line' => 20,
                                    'column' => 108,
                                ],
                                'end' => [
                                    'line' => 70,
                                    'column' => 2,
                                ],
                            ],
                        ],
                        'severity' => 'major',
                        'fingerprint' => '98196e2f80a0b0a738751d16a046f76f',
                    ],
                ],
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
                'expected' => [
                    [
                        'type' => 'issue',
                        'check_name' => 'task-name',
                        'description' => 'Custom warning',
                        'categories' => [
                            'Bug Risk',
                        ],
                        'location' => [
                            'path' => 'unknown',
                            'lines' => [
                                'begin' => 1,
                                'end' => 1,
                            ],
                        ],
                        'severity' => 'minor',
                        'fingerprint' => '814fd4817abd5d8c288464627779adef',
                    ],
                ],
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
                'expected' => [
                    [
                        'type' => 'issue',
                        'check_name' => 'task-name: Codestyle.rule',
                        'description' => 'Custom warning',
                        'categories' => [
                            'Bug Risk',
                        ],
                        'location' => [
                            'path' => 'unknown',
                            'lines' => [
                                'begin' => 1,
                                'end' => 1,
                            ],
                        ],
                        'severity' => 'minor',
                        'fingerprint' => '7fbbaaabbe040ab6ba6efff4d451dd76',
                    ],
                ],
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
                'expected' => [
                    [
                        'type' => 'issue',
                        'check_name' => 'task-name: Codestyle.rule',
                        'description' => 'Custom warning (See: https://example.org)',
                        'categories' => [
                            'Bug Risk',
                        ],
                        'location' => [
                            'path' => 'unknown',
                            'lines' => [
                                'begin' => 1,
                                'end' => 1,
                            ],
                        ],
                        'severity' => 'minor',
                        'fingerprint' => '7fbbaaabbe040ab6ba6efff4d451dd76',
                    ],
                ],
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
                'expected' => [
                    [
                        'type' => 'issue',
                        'check_name' => 'task-name: Codestyle.rule',
                        'description' => 'Custom warning (See: https://example.org)',
                        'categories' => [
                            'Bug Risk',
                        ],
                        'location' => [
                            'path' => 'rotten/code/example.php',
                            'lines' => [
                                'begin' => 1,
                                'end' => 1,
                            ],
                        ],
                        'severity' => 'minor',
                        'fingerprint' => '1ef6558125a33b3461460b29e310e205',
                    ],
                ],
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
                'expected' => [
                    [
                        'type' => 'issue',
                        'check_name' => 'task-name: Codestyle.rule',
                        'description' => 'Custom warning (See: https://example.org)',
                        'categories' => [
                            'Bug Risk',
                        ],
                        'location' => [
                            'path' => 'rotten/code/example.php',
                            'lines' => [
                                'begin' => 20,
                                'end' => 20,
                            ],
                        ],
                        'severity' => 'minor',
                        'fingerprint' => '9c2f3f01fcc58385821f75fb155beed5',
                    ],
                ],
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
                'expected' => [
                    [
                        'type' => 'issue',
                        'check_name' => 'task-name: Codestyle.rule',
                        'description' => 'Custom warning (See: https://example.org)',
                        'categories' => [
                            'Bug Risk',
                        ],
                        'location' => [
                            'path' => 'rotten/code/example.php',
                            'positions' => [
                                'begin' => [
                                    'line' => 20,
                                    'column' => 108,
                                ],
                                'end' => [
                                    'line' => 20,
                                    'column' => 108,
                                ],
                            ],
                        ],
                        'severity' => 'minor',
                        'fingerprint' => '38f8e35abc9ba0baacaf40b5ea878877',
                    ],
                ],
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
                'expected' => [
                    [
                        'type' => 'issue',
                        'check_name' => 'task-name: Codestyle.rule',
                        'description' => 'Custom warning (See: https://example.org)',
                        'categories' => [
                            'Bug Risk',
                        ],
                        'location' => [
                            'path' => 'rotten/code/example.php',
                            'positions' => [
                                'begin' => [
                                    'line' => 20,
                                    'column' => 108,
                                ],
                                'end' => [
                                    'line' => 70,
                                    'column' => 2,
                                ],
                            ],
                        ],
                        'severity' => 'minor',
                        'fingerprint' => '98196e2f80a0b0a738751d16a046f76f',
                    ],
                ],
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
    public function testWriteErrors(array $expected, string $task, DiagnosticBuffer $diagnostic): void
    {
        $tempDir    = self::$tempdir . '/' . uniqid('phpcq', true);
        $report     = new ReportBuffer();
        $toolReport = $report->createTaskReport($task);
        $toolReport->addDiagnostic($diagnostic);
        $report->complete(ReportInterface::STATUS_FAILED);

        CodeClimateReportWriter::writeReport($tempDir, $report, TaskReportInterface::SEVERITY_INFO);
        self::assertSame($expected, json_decode(file_get_contents($tempDir . '/code-climate.json'), true));
    }

    public function ignoredSeveritiesProvider(): array
    {
        return [
            'ignores info severity'   => [TaskReportInterface::SEVERITY_INFO],
            'ignores notice severity' => [TaskReportInterface::SEVERITY_MARGINAL],
        ];
    }

    /**
     * @dataProvider ignoredSeveritiesProvider
     */
    public function testIgnoresMessagesWithLowerSeverityThanWarning(string $severity): void
    {
        $tempDir    = self::$tempdir . '/' . uniqid('phpcq', true);
        $report     = new ReportBuffer();
        $toolReport = $report->createTaskReport('task-name');
        $toolReport->addDiagnostic(new DiagnosticBuffer($severity, 'Message', null, null, null, null, null));
        $report->complete(ReportInterface::STATUS_FAILED);

        CodeClimateReportWriter::writeReport($tempDir, $report, TaskReportInterface::SEVERITY_MINOR);
        self::assertSame([], json_decode(file_get_contents($tempDir . '/code-climate.json'), true));
    }
}
