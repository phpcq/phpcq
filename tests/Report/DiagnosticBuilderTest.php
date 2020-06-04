<?php

declare(strict_types=1);

namespace Phpcq\Test\Report;

use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\FileRangeBuffer;
use Phpcq\Report\DiagnosticBuilder;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Report\DiagnosticBuilder */
final class DiagnosticBuilderTest extends TestCase
{
    public function testBuildsMinimal(): void
    {
        $report  = $this->getMockForAbstractClass(ToolReportInterface::class);
        $builder = new DiagnosticBuilder(
            $report,
            'error',
            'This is an error',
            function (DiagnosticBuffer $diagnostic, DiagnosticBuilder $sender) use (&$builder) {
                $this->assertSame($builder, $sender);
                $this->assertDiagnosticIs(
                    'error',
                    'This is an error',
                    null,
                    null,
                    $diagnostic
                );
            }
        );

        $this->assertSame($report, $builder->end());
    }

    public function testBuildsWithSource(): void
    {
        $report  = $this->getMockForAbstractClass(ToolReportInterface::class);
        $builder = new DiagnosticBuilder(
            $report,
            'error',
            'This is an error',
            function (DiagnosticBuffer $diagnostic, DiagnosticBuilder $sender) use (&$builder) {
                $this->assertSame($builder, $sender);
                $this->assertDiagnosticIs(
                    'error',
                    'This is an error',
                    'some source',
                    null,
                    $diagnostic
                );
            }
        );

        $this->assertSame($builder, $builder->fromSource('some source'));
        $this->assertSame($report, $builder->end());
    }

    public function testBuildsWithSourceAndFileRange(): void
    {
        $report  = $this->getMockForAbstractClass(ToolReportInterface::class);
        $builder = new DiagnosticBuilder(
            $report,
            'error',
            'This is an error',
            function (DiagnosticBuffer $diagnostic, DiagnosticBuilder $sender) use (&$builder) {
                $this->assertSame($builder, $sender);
                $this->assertDiagnosticIs(
                    'error',
                    'This is an error',
                    'some source',
                    [new FileRangeBuffer('some/file', null, null, null, null)],
                    $diagnostic
                );
            }
        );

        $this->assertSame($builder, $builder->fromSource('some source'));
        $this->assertSame($builder, $builder->forFile('some/file')->end());
        $this->assertSame($report, $builder->end());
    }

    public function testEndIsCalledForPendingBuilder(): void
    {
        $report  = $this->getMockForAbstractClass(ToolReportInterface::class);
        $builder = new DiagnosticBuilder(
            $report,
            'error',
            'This is an error',
            function (DiagnosticBuffer $diagnostic, DiagnosticBuilder $sender) use (&$builder) {
                $this->assertSame($builder, $sender);
                $this->assertDiagnosticIs(
                    'error',
                    'This is an error',
                    null,
                    [
                        new FileRangeBuffer('some/file', null, null, null, null),
                        new FileRangeBuffer('another/file', null, null, null, null),
                    ],
                    $diagnostic
                );
            }
        );

        // "forgotten" end calls on file builders.
        $builder->forFile('some/file');
        $builder->forFile('another/file');

        $builder->end();
    }

    private function assertDiagnosticIs(
        string $expectedSeverity,
        string $expectedMessage,
        ?string $expectedSource,
        ?array $fileRanges,
        DiagnosticBuffer $diagnostic
    ): void {
        $this->assertSame($expectedSeverity, $diagnostic->getSeverity());
        $this->assertSame($expectedMessage, $diagnostic->getMessage());
        $this->assertSame($expectedSource, $diagnostic->getSource());
        $this->assertEquals($fileRanges ?? [], iterator_to_array($diagnostic->getFileRanges()));
    }
}
