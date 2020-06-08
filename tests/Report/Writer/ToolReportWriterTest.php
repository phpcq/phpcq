<?php

declare(strict_types=1);

namespace Report\Writer;

use DOMDocument;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Report;
use Phpcq\Report\Writer\ToolReportWriter;

use function file_get_contents;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DATE_ATOM;

/**
 * @covers \Phpcq\Report\Writer\AbstractReportWriter
 * @covers \Phpcq\Report\Writer\ToolReportWriter
 */
final class ToolReportWriterTest extends AbstractWriterTest
{
    public function testWriteEmptyReport(): void
    {
        $report = new ReportBuffer();
        $report->complete(Report::STATUS_PASSED);

        $tempDir = sys_get_temp_dir() . '/' . uniqid('phpcq', true);
        $fileName = $tempDir . '/tool-report.xml';

        ToolReportWriter::writeReport($tempDir, $report);

        // phpcs:disable
        $xml = <<<'XML'
<?xml version="1.0"?>
<phpcq:tool-report xmlns:phpcq="https://phpcq.github.io/v1/tool-report.xsd" status="passed" started_at="%s" completed_at="%s">
  <phpcq:tools/>
</phpcq:tool-report>

XML;
        // phpcs:enable
        $xml = sprintf($xml, $report->getStartedAt()->format(DATE_ATOM), $report->getCompletedAt()->format(DATE_ATOM));

        $this->assertEquals($xml, file_get_contents($fileName));
        $this->assertSchemaValidate($fileName);

        unlink($fileName);
    }

    public function testWriteFullFeaturedReport(): void
    {
        $report = $this->createFullFeaturedReport();

        $tempDir = sys_get_temp_dir() . '/' . uniqid('phpcq', true);
        $fileName = $tempDir . '/tool-report.xml';

        ToolReportWriter::writeReport($tempDir, $report);

        // phpcs:disable
        $xml = <<<'XML'
<?xml version="1.0"?>
<phpcq:tool-report xmlns:phpcq="https://phpcq.github.io/v1/tool-report.xsd" status="passed" started_at="%s" completed_at="%s">
  <phpcq:tools>
    <phpcq:tool name="tool" status="passed">
      <phpcq:diagnostics>
        <phpcq:diagnostic severity="info" source="baz">Foo bar</phpcq:diagnostic>
        <phpcq:diagnostic line="1" file="example.php" severity="error" external_info_url="https://example.org/super-helpful-tip">Failure</phpcq:diagnostic>
        <phpcq:diagnostic line="1" column="2" file="example.php" severity="error" external_info_url="https://example.org/super-helpful-tip">Failure</phpcq:diagnostic>
        <phpcq:diagnostic line="1" column="2" line_end="3" file="example2.php" severity="error" external_info_url="https://example.org/super-helpful-tip">Failure</phpcq:diagnostic>
        <phpcq:diagnostic line="1" column="2" line_end="3" column_end="4" file="example2.php" severity="error" external_info_url="https://example.org/super-helpful-tip">Failure</phpcq:diagnostic>
      </phpcq:diagnostics>
      <phpcq:attachments>
        <phpcq:attachment name="foo.xml" filename="tool-foo.xml" mime="application/xml"/>
        <phpcq:attachment name="bar.xml" filename="tool-bar.xml"/>
      </phpcq:attachments>
      <phpcq:diffs>
        <phpcq:diff name="diff1.diff" filename="tool-diff1.diff"/>
      </phpcq:diffs>
    </phpcq:tool>
  </phpcq:tools>
</phpcq:tool-report>

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

    public function testWriteFilteredReport(): void
    {
        $report = $this->createFullFeaturedReport();

        $tempDir = sys_get_temp_dir() . '/' . uniqid('phpcq', true);
        $fileName = $tempDir . '/tool-report.xml';

        ToolReportWriter::writeReport($tempDir, $report, ToolReportInterface::SEVERITY_WARNING);

        // phpcs:disable
        $xml = <<<'XML'
<?xml version="1.0"?>
<phpcq:tool-report xmlns:phpcq="https://phpcq.github.io/v1/tool-report.xsd" status="passed" started_at="%s" completed_at="%s">
  <phpcq:tools>
    <phpcq:tool name="tool" status="passed">
      <phpcq:diagnostics>
        <phpcq:diagnostic line="1" file="example.php" severity="error" external_info_url="https://example.org/super-helpful-tip">Failure</phpcq:diagnostic>
        <phpcq:diagnostic line="1" column="2" file="example.php" severity="error" external_info_url="https://example.org/super-helpful-tip">Failure</phpcq:diagnostic>
        <phpcq:diagnostic line="1" column="2" line_end="3" file="example2.php" severity="error" external_info_url="https://example.org/super-helpful-tip">Failure</phpcq:diagnostic>
        <phpcq:diagnostic line="1" column="2" line_end="3" column_end="4" file="example2.php" severity="error" external_info_url="https://example.org/super-helpful-tip">Failure</phpcq:diagnostic>
      </phpcq:diagnostics>
      <phpcq:attachments>
        <phpcq:attachment name="foo.xml" filename="tool-foo.xml" mime="application/xml"/>
        <phpcq:attachment name="bar.xml" filename="tool-bar.xml"/>
      </phpcq:attachments>
      <phpcq:diffs>
        <phpcq:diff name="diff1.diff" filename="tool-diff1.diff"/>
      </phpcq:diffs>
    </phpcq:tool>
  </phpcq:tools>
</phpcq:tool-report>

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
        $dom = new DOMDocument('1.0');
        $dom->load($fileName);
        $this->assertTrue($dom->schemaValidate(__DIR__ . '/../../../doc/tool-report.xsd'));
    }
}
