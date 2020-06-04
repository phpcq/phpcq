<?php

declare(strict_types=1);

namespace Report\Writer;

use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\FileRangeBuffer;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Report;
use Phpcq\Report\Writer\FileReportWriter;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;
use function uniqid;
use function unlink;

use const DATE_ATOM;

final class FileReportWriterTest extends TestCase
{
    public function testWriteEmptyReport(): void
    {
        $report = new ReportBuffer();
        $report->complete(Report::STATUS_PASSED);

        $tempDir = sys_get_temp_dir() . '/' . uniqid('phpcq', true);
        $fileName = $tempDir . '/file-report.xml';

        FileReportWriter::writeReport($tempDir, $report);

        // phpcs:disable
        $xml = <<<'XML'
<?xml version="1.0"?>
<phpcq:file-report xmlns:phpcq="https://phpcq.github.io/v1/file-report.xsd" status="passed" started_at="%s" completed_at="%s">
  <phpcq:abstract/>
  <phpcq:global/>
  <phpcq:files/>
</phpcq:file-report>

XML;
        // phpcs:enable
        $xml = sprintf($xml, $report->getStartedAt()->format(DATE_ATOM), $report->getCompletedAt()->format(DATE_ATOM));


        $this->assertEquals(
            $xml,
            file_get_contents($fileName)
        );

        $this->assertSchemaValidate($fileName);

        unlink($fileName);
    }

    public function testWriteFullFeaturedReport(): void
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

        $tempDir = sys_get_temp_dir() . '/' . uniqid('phpcq', true);
        $fileName = $tempDir . '/file-report.xml';

        FileReportWriter::writeReport($tempDir, $report);

        // phpcs:disable
        $xml = <<<'XML'
<?xml version="1.0"?>
<phpcq:file-report xmlns:phpcq="https://phpcq.github.io/v1/file-report.xsd" status="passed" started_at="%s" completed_at="%s">
  <phpcq:abstract>
    <phpcq:tool name="tool" status="passed">
      <phpcq:attachments>
        <phpcq:attachment name="foo.xml" filename="tool-foo.xml"/>
      </phpcq:attachments>
    </phpcq:tool>
    <phpcq:tool name="tool2" status="failed"/>
  </phpcq:abstract>
  <phpcq:global>
    <phpcq:diagnostic severity="info" source="baz" tool="tool">Foo bar</phpcq:diagnostic>
  </phpcq:global>
  <phpcq:files>
    <phpcq:file name="example.php">
      <phpcq:diagnostic line="1" severity="error" tool="tool">Failure</phpcq:diagnostic>
      <phpcq:diagnostic line="1" column="2" severity="error" tool="tool">Failure</phpcq:diagnostic>
    </phpcq:file>
    <phpcq:file name="example2.php">
      <phpcq:diagnostic line="1" column="2" line_end="3" severity="error" tool="tool">Failure</phpcq:diagnostic>
      <phpcq:diagnostic line="1" column="2" line_end="3" column_end="4" severity="error" tool="tool">Failure</phpcq:diagnostic>
    </phpcq:file>
  </phpcq:files>
</phpcq:file-report>

XML;
        // phpcs:enable

        $this->assertEquals(
            sprintf(
                $xml,
                $report->getStartedAt()->format(DATE_ATOM),
                $report->getCompletedAt()->format(DATE_ATOM),
            ),
            file_get_contents($fileName)
        );

        $this->assertSchemaValidate($fileName);

        unlink($fileName);
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    private function assertSchemaValidate(string $fileName): void
    {
        $this->markTestSkipped('Schema not implemented yet');
        // FIXME: Validate schema
        // $dom = new DOMDocument('1.0');
        // $dom->load($fileName);
        // $this->assertTrue($dom->schemaValidate(__DIR__ . '/../../../doc/file-report.xsd'));
    }
}
