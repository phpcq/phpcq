<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Report\Writer;

use DOMDocument;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Report\Buffer\ReportBuffer;
use Phpcq\Runner\Report\Report;
use Phpcq\Runner\Report\Writer\FileReportWriter;
use Phpcq\Runner\Test\TemporaryFileProducingTestTrait;

use function file_get_contents;
use function sprintf;
use function uniqid;
use function unlink;

use const DATE_ATOM;

/**
 * @covers \Phpcq\Runner\Report\Writer\AbstractReportWriter
 * @covers \Phpcq\Runner\Report\Writer\FileReportWriter
 */
final class FileReportWriterTest extends AbstractWriterTest
{
    use TemporaryFileProducingTestTrait;

    public function testWriteEmptyReport(): void
    {
        $report = new ReportBuffer();
        $report->complete(Report::STATUS_PASSED);

        $tempDir = self::$tempdir . '/' . uniqid('phpcq', true);
        $fileName = $tempDir . '/file-report.xml';

        FileReportWriter::writeReport($tempDir, $report);

        // phpcs:disable
        $xml = <<<'XML'
<?xml version="1.0"?>
<phpcq:file-report xmlns:phpcq="https://phpcq.github.io/schema/v1/file-report.xsd" status="passed" started_at="%s" completed_at="%s">
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
        $tempDir = self::$tempdir . '/' . uniqid('phpcq', true);
        $fileName = $tempDir . '/file-report.xml';

        FileReportWriter::writeReport($tempDir, $report);

        // phpcs:disable
        $xml = <<<'XML'
<?xml version="1.0"?>
<phpcq:file-report xmlns:phpcq="https://phpcq.github.io/schema/v1/file-report.xsd" status="passed" started_at="%s" completed_at="%s">
  <phpcq:abstract>
    <phpcq:tool name="tool" status="passed" version="unknown">
      <phpcq:attachments>
        <phpcq:attachment name="foo.xml" filename="tool-foo.xml" mime="application/xml"/>
        <phpcq:attachment name="bar.xml" filename="tool-bar.xml"/>
      </phpcq:attachments>
      <phpcq:diffs>
        <phpcq:diff name="diff1.diff" filename="tool-diff1.diff"/>
      </phpcq:diffs>
    </phpcq:tool>
    <phpcq:tool name="tool2" status="failed" version="unknown"/>
  </phpcq:abstract>
  <phpcq:global>
    <phpcq:diagnostic severity="info" source="baz" tool="tool">
      <phpcq:message>Foo bar</phpcq:message>
    </phpcq:diagnostic>
  </phpcq:global>
  <phpcq:files>
    <phpcq:file name="example.php">
      <phpcq:diagnostic line="1" severity="major" external_info_url="https://example.org/super-helpful-tip" tool="tool">
        <phpcq:class_name name="Some\Class\Name"/>
        <phpcq:class_name name="Another\Class\Name"/>
        <phpcq:category name="category1"/>
        <phpcq:category name="category2"/>
        <phpcq:message>Failure</phpcq:message>
      </phpcq:diagnostic>
      <phpcq:diagnostic line="1" column="2" severity="major" external_info_url="https://example.org/super-helpful-tip" tool="tool">
        <phpcq:class_name name="Some\Class\Name"/>
        <phpcq:class_name name="Another\Class\Name"/>
        <phpcq:category name="category1"/>
        <phpcq:category name="category2"/>
        <phpcq:message>Failure</phpcq:message>
      </phpcq:diagnostic>
    </phpcq:file>
    <phpcq:file name="example2.php">
      <phpcq:diagnostic line="1" column="2" line_end="3" severity="major" external_info_url="https://example.org/super-helpful-tip" tool="tool">
        <phpcq:class_name name="Some\Class\Name"/>
        <phpcq:class_name name="Another\Class\Name"/>
        <phpcq:category name="category1"/>
        <phpcq:category name="category2"/>
        <phpcq:message>Failure</phpcq:message>
      </phpcq:diagnostic>
      <phpcq:diagnostic line="1" column="2" line_end="3" column_end="4" severity="major" external_info_url="https://example.org/super-helpful-tip" tool="tool">
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
        $tempDir = self::$tempdir . '/' . uniqid('phpcq', true);
        $fileName = $tempDir . '/file-report.xml';

        FileReportWriter::writeReport($tempDir, $report, TaskReportInterface::SEVERITY_MINOR);

        // phpcs:disable
        $xml = <<<'XML'
<?xml version="1.0"?>
<phpcq:file-report xmlns:phpcq="https://phpcq.github.io/schema/v1/file-report.xsd" status="passed" started_at="%s" completed_at="%s">
  <phpcq:abstract>
    <phpcq:tool name="tool" status="passed" version="unknown">
      <phpcq:attachments>
        <phpcq:attachment name="foo.xml" filename="tool-foo.xml" mime="application/xml"/>
        <phpcq:attachment name="bar.xml" filename="tool-bar.xml"/>
      </phpcq:attachments>
      <phpcq:diffs>
        <phpcq:diff name="diff1.diff" filename="tool-diff1.diff"/>
      </phpcq:diffs>
    </phpcq:tool>
    <phpcq:tool name="tool2" status="failed" version="unknown"/>
  </phpcq:abstract>
  <phpcq:global/>
  <phpcq:files>
    <phpcq:file name="example.php">
      <phpcq:diagnostic line="1" severity="major" external_info_url="https://example.org/super-helpful-tip" tool="tool">
        <phpcq:class_name name="Some\Class\Name"/>
        <phpcq:class_name name="Another\Class\Name"/>
        <phpcq:category name="category1"/>
        <phpcq:category name="category2"/>
        <phpcq:message>Failure</phpcq:message>
      </phpcq:diagnostic>
      <phpcq:diagnostic line="1" column="2" severity="major" external_info_url="https://example.org/super-helpful-tip" tool="tool">
        <phpcq:class_name name="Some\Class\Name"/>
        <phpcq:class_name name="Another\Class\Name"/>
        <phpcq:category name="category1"/>
        <phpcq:category name="category2"/>
        <phpcq:message>Failure</phpcq:message>
      </phpcq:diagnostic>
    </phpcq:file>
    <phpcq:file name="example2.php">
      <phpcq:diagnostic line="1" column="2" line_end="3" severity="major" external_info_url="https://example.org/super-helpful-tip" tool="tool">
        <phpcq:class_name name="Some\Class\Name"/>
        <phpcq:class_name name="Another\Class\Name"/>
        <phpcq:category name="category1"/>
        <phpcq:category name="category2"/>
        <phpcq:message>Failure</phpcq:message>
      </phpcq:diagnostic>
      <phpcq:diagnostic line="1" column="2" line_end="3" column_end="4" severity="major" external_info_url="https://example.org/super-helpful-tip" tool="tool">
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
        $this->assertTrue($dom->schemaValidate(__DIR__ . '/../../../vendor/phpcq/schema/v1/file-report.xsd'));
    }
}
