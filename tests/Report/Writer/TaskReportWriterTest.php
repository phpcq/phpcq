<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Writer;

use DOMDocument;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Report;
use Phpcq\Report\Writer\TaskReportWriter;
use Phpcq\Test\TemporaryFileProducingTestTrait;

use function file_get_contents;
use function sprintf;
use function uniqid;
use function unlink;

use const DATE_ATOM;

/**
 * @covers \Phpcq\Report\Writer\AbstractReportWriter
 * @covers \Phpcq\Report\Writer\TaskReportWriter
 */
final class TaskReportWriterTest extends AbstractWriterTest
{
    use TemporaryFileProducingTestTrait;

    public function testWriteEmptyReport(): void
    {
        $report = new ReportBuffer();
        $report->complete(Report::STATUS_PASSED);

        $tempDir = self::$tempdir . '/' . uniqid('phpcq', true);
        $fileName = $tempDir . '/tool-report.xml';

        TaskReportWriter::writeReport($tempDir, $report);

        // phpcs:disable
        $xml = <<<'XML'
<?xml version="1.0"?>
<phpcq:tool-report xmlns:phpcq="https://phpcq.github.io/schema/v1/tool-report.xsd" status="passed" started_at="%s" completed_at="%s">
  <phpcq:tools/>
</phpcq:tool-report>

XML;
        // phpcs:enable
        $xml = sprintf($xml, $report->getStartedAt()->format(DATE_ATOM), $report->getCompletedAt()->format(DATE_ATOM));

        $this->assertSchemaValidate($fileName);
        $this->assertEquals($xml, file_get_contents($fileName));

        unlink($fileName);
    }

    public function testWriteFullFeaturedReport(): void
    {
        $report = $this->createFullFeaturedReport();

        $tempDir = self::$tempdir . '/' . uniqid('phpcq', true);
        $fileName = $tempDir . '/tool-report.xml';

        TaskReportWriter::writeReport($tempDir, $report);

        // phpcs:disable
        $xml = <<<'XML'
<?xml version="1.0"?>
<phpcq:tool-report xmlns:phpcq="https://phpcq.github.io/schema/v1/tool-report.xsd" status="passed" started_at="%s" completed_at="%s">
  <phpcq:tools>
    <phpcq:tool name="tool" status="passed" version="1.0.0">
      <phpcq:diagnostics>
        <phpcq:diagnostic severity="info" source="baz">
          <phpcq:message>Foo bar</phpcq:message>
        </phpcq:diagnostic>
        <phpcq:diagnostic severity="major" external_info_url="https://example.org/super-helpful-tip">
          <phpcq:class_name name="Some\Class\Name"/>
          <phpcq:class_name name="Another\Class\Name"/>
          <phpcq:category name="category1"/>
          <phpcq:category name="category2"/>
          <phpcq:file line="1" name="example.php"/>
          <phpcq:file line="1" column="2" name="example.php"/>
          <phpcq:file line="1" column="2" line_end="3" name="example2.php"/>
          <phpcq:file line="1" column="2" line_end="3" column_end="4" name="example2.php"/>
          <phpcq:message>Failure</phpcq:message>
        </phpcq:diagnostic>
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
        $fileName = $tempDir . '/tool-report.xml';

        TaskReportWriter::writeReport($tempDir, $report, TaskReportInterface::SEVERITY_MINOR);

        // phpcs:disable
        $xml = <<<'XML'
<?xml version="1.0"?>
<phpcq:tool-report xmlns:phpcq="https://phpcq.github.io/schema/v1/tool-report.xsd" status="passed" started_at="%s" completed_at="%s">
  <phpcq:tools>
    <phpcq:tool name="tool" status="passed" version="1.0.0">
      <phpcq:diagnostics>
        <phpcq:diagnostic severity="major" external_info_url="https://example.org/super-helpful-tip">
          <phpcq:class_name name="Some\Class\Name"/>
          <phpcq:class_name name="Another\Class\Name"/>
          <phpcq:category name="category1"/>
          <phpcq:category name="category2"/>
          <phpcq:file line="1" name="example.php"/>
          <phpcq:file line="1" column="2" name="example.php"/>
          <phpcq:file line="1" column="2" line_end="3" name="example2.php"/>
          <phpcq:file line="1" column="2" line_end="3" column_end="4" name="example2.php"/>
          <phpcq:message>Failure</phpcq:message>
        </phpcq:diagnostic>
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
        $this->assertTrue($dom->schemaValidate(__DIR__ . '/../../../vendor/phpcq/schema/v1/tool-report.xsd'));
    }
}
