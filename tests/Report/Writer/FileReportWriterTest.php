<?php

declare(strict_types=1);

namespace Report\Writer;

use DOMDocument;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Report;
use Phpcq\Report\Writer\FileReportWriter;

use function file_get_contents;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DATE_ATOM;

/**
 * @covers \Phpcq\Report\Writer\AbstractReportWriter
 * @covers \Phpcq\Report\Writer\FileReportWriter
 */
final class FileReportWriterTest extends AbstractWriterTest
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

        $this->assertSchemaValidate($fileName);
        $this->assertEquals(
            $xml,
            file_get_contents($fileName)
        );

        unlink($fileName);
    }

    public function testWriteFullFeaturedReport(): void
    {
        $report = $this->createFullFeaturedReport();
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
        <phpcq:attachment name="foo.xml" filename="tool-foo.xml" mime="application/xml"/>
        <phpcq:attachment name="bar.xml" filename="tool-bar.xml"/>
      </phpcq:attachments>
      <phpcq:diffs>
        <phpcq:diff name="diff1.diff" filename="tool-diff1.diff"/>
      </phpcq:diffs>
    </phpcq:tool>
    <phpcq:tool name="tool2" status="failed"/>
  </phpcq:abstract>
  <phpcq:global>
    <phpcq:diagnostic severity="info" source="baz" tool="tool">
      <phpcq:message>Foo bar</phpcq:message>
    </phpcq:diagnostic>
  </phpcq:global>
  <phpcq:files>
    <phpcq:file name="example.php">
      <phpcq:diagnostic line="1" severity="error" external_info_url="https://example.org/super-helpful-tip" tool="tool">
        <phpcq:class_name name="Some\Class\Name"/>
        <phpcq:class_name name="Another\Class\Name"/>
        <phpcq:category name="category1"/>
        <phpcq:category name="category2"/>
        <phpcq:message>Failure</phpcq:message>
      </phpcq:diagnostic>
      <phpcq:diagnostic line="1" column="2" severity="error" external_info_url="https://example.org/super-helpful-tip" tool="tool">
        <phpcq:class_name name="Some\Class\Name"/>
        <phpcq:class_name name="Another\Class\Name"/>
        <phpcq:category name="category1"/>
        <phpcq:category name="category2"/>
        <phpcq:message>Failure</phpcq:message>
      </phpcq:diagnostic>
    </phpcq:file>
    <phpcq:file name="example2.php">
      <phpcq:diagnostic line="1" column="2" line_end="3" severity="error" external_info_url="https://example.org/super-helpful-tip" tool="tool">
        <phpcq:class_name name="Some\Class\Name"/>
        <phpcq:class_name name="Another\Class\Name"/>
        <phpcq:category name="category1"/>
        <phpcq:category name="category2"/>
        <phpcq:message>Failure</phpcq:message>
      </phpcq:diagnostic>
      <phpcq:diagnostic line="1" column="2" line_end="3" column_end="4" severity="error" external_info_url="https://example.org/super-helpful-tip" tool="tool">
        <phpcq:class_name name="Some\Class\Name"/>
        <phpcq:class_name name="Another\Class\Name"/>
        <phpcq:category name="category1"/>
        <phpcq:category name="category2"/>
        <phpcq:message>Failure</phpcq:message>
      </phpcq:diagnostic>
    </phpcq:file>
  </phpcq:files>
</phpcq:file-report>

XML;
        // phpcs:enable

        $this->assertSchemaValidate($fileName);
        $this->assertEquals(
            sprintf(
                $xml,
                $report->getStartedAt()->format(DATE_ATOM),
                $report->getCompletedAt()->format(DATE_ATOM),
            ),
            file_get_contents($fileName)
        );

        unlink($fileName);
    }

    public function testWriteFilteredReport(): void
    {
        $report = $this->createFullFeaturedReport();
        $tempDir = sys_get_temp_dir() . '/' . uniqid('phpcq', true);
        $fileName = $tempDir . '/file-report.xml';

        FileReportWriter::writeReport($tempDir, $report, ToolReportInterface::SEVERITY_WARNING);

        // phpcs:disable
        $xml = <<<'XML'
<?xml version="1.0"?>
<phpcq:file-report xmlns:phpcq="https://phpcq.github.io/v1/file-report.xsd" status="passed" started_at="%s" completed_at="%s">
  <phpcq:abstract>
    <phpcq:tool name="tool" status="passed">
      <phpcq:attachments>
        <phpcq:attachment name="foo.xml" filename="tool-foo.xml" mime="application/xml"/>
        <phpcq:attachment name="bar.xml" filename="tool-bar.xml"/>
      </phpcq:attachments>
      <phpcq:diffs>
        <phpcq:diff name="diff1.diff" filename="tool-diff1.diff"/>
      </phpcq:diffs>
    </phpcq:tool>
    <phpcq:tool name="tool2" status="failed"/>
  </phpcq:abstract>
  <phpcq:global/>
  <phpcq:files>
    <phpcq:file name="example.php">
      <phpcq:diagnostic line="1" severity="error" external_info_url="https://example.org/super-helpful-tip" tool="tool">
        <phpcq:class_name name="Some\Class\Name"/>
        <phpcq:class_name name="Another\Class\Name"/>
        <phpcq:category name="category1"/>
        <phpcq:category name="category2"/>
        <phpcq:message>Failure</phpcq:message>
      </phpcq:diagnostic>
      <phpcq:diagnostic line="1" column="2" severity="error" external_info_url="https://example.org/super-helpful-tip" tool="tool">
        <phpcq:class_name name="Some\Class\Name"/>
        <phpcq:class_name name="Another\Class\Name"/>
        <phpcq:category name="category1"/>
        <phpcq:category name="category2"/>
        <phpcq:message>Failure</phpcq:message>
      </phpcq:diagnostic>
    </phpcq:file>
    <phpcq:file name="example2.php">
      <phpcq:diagnostic line="1" column="2" line_end="3" severity="error" external_info_url="https://example.org/super-helpful-tip" tool="tool">
        <phpcq:class_name name="Some\Class\Name"/>
        <phpcq:class_name name="Another\Class\Name"/>
        <phpcq:category name="category1"/>
        <phpcq:category name="category2"/>
        <phpcq:message>Failure</phpcq:message>
      </phpcq:diagnostic>
      <phpcq:diagnostic line="1" column="2" line_end="3" column_end="4" severity="error" external_info_url="https://example.org/super-helpful-tip" tool="tool">
        <phpcq:class_name name="Some\Class\Name"/>
        <phpcq:class_name name="Another\Class\Name"/>
        <phpcq:category name="category1"/>
        <phpcq:category name="category2"/>
        <phpcq:message>Failure</phpcq:message>
      </phpcq:diagnostic>
    </phpcq:file>
  </phpcq:files>
</phpcq:file-report>

XML;
        // phpcs:enable

        $this->assertSchemaValidate($fileName);
        $this->assertEquals(
            sprintf(
                $xml,
                $report->getStartedAt()->format(DATE_ATOM),
                $report->getCompletedAt()->format(DATE_ATOM),
            ),
            file_get_contents($fileName)
        );

        unlink($fileName);
    }

    private function assertSchemaValidate(string $fileName): void
    {
        $dom = new DOMDocument('1.0');
        $dom->load($fileName);
        $this->assertTrue($dom->schemaValidate(__DIR__ . '/../../../doc/file-report.xsd'));
    }
}
