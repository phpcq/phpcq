<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Report;

use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Report\AttachmentBuilder;
use Phpcq\Runner\Report\Buffer\AttachmentBuffer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/** @covers \Phpcq\Runner\Report\AttachmentBuilder */
final class AttachmentBuilderTest extends TestCase
{
    public function testCallingEndThrowsWhenNoDataHasBeenAdded(): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->getMock();

        $filesystem->expects($this->never())->method('dumpFile');

        $report  = $this->createMock(TaskReportInterface::class);
        $builder = new AttachmentBuilder(
            'some-file.txt',
            $report,
            '/path/to/temp/directory',
            $filesystem,
            function () use (&$builder) {
                $this->fail('Should not have been called.');
            }
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Must provide content either via fromFile() or via fromString()');

        $builder->end();
    }

    public function testBuildsFromFile(): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->getMock();

        $filesystem->expects($this->never())->method('dumpFile');

        $report  = $this->createMock(TaskReportInterface::class);
        $builder = new AttachmentBuilder(
            'some-file.txt',
            $report,
            '/path/to/temp/directory',
            $filesystem,
            function (AttachmentBuffer $diagnostic, AttachmentBuilder $sender) use (&$builder) {
                $this->assertSame($builder, $sender);
                $this->assertAttachmentIs(
                    '/path/to/other/file',
                    'some-file.txt',
                    null,
                    $diagnostic
                );
            }
        );
        $builder->fromFile('/path/to/other/file')->end();

        $this->assertSame($report, $builder->end());
    }

    public function testBuildingFromFileThrowsForNonAbsolutePath(): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->getMock();

        $filesystem->expects($this->never())->method('dumpFile');

        $report  = $this->createMock(TaskReportInterface::class);
        $builder = new AttachmentBuilder(
            'some-file.txt',
            $report,
            '/path/to/temp/directory',
            $filesystem,
            function () use (&$builder) {
                $this->fail('Should not have been called.');
            }
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Absolute path expected but got: "relative/path/to/file"');

        $builder->fromFile('relative/path/to/file')->end();
    }

    public function testBuildsFromBuffer(): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->getMock();

        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->willReturnCallback(function (string $filePath, string $data) {
                $this->assertStringStartsWith('/path/to/temp/directory/some-file.txt', $filePath);
                $this->assertSame('file contents', $data);
            });

        $report  = $this->createMock(TaskReportInterface::class);
        $builder = new AttachmentBuilder(
            'some-file.txt',
            $report,
            '/path/to/temp/directory',
            $filesystem,
            function (AttachmentBuffer $diagnostic, AttachmentBuilder $sender) use (&$builder) {
                $this->assertSame($builder, $sender);
                $this->assertAttachmentIs(
                    '/path/to/temp/directory/some-file.txt',
                    'some-file.txt',
                    null,
                    $diagnostic
                );
            }
        );
        $builder->fromString('file contents')->end();

        $this->assertSame($report, $builder->end());
    }

    public function testGeneratesWithMimeType(): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->getMock();

        $filesystem->expects($this->once())->method('dumpFile');

        $report  = $this->createMock(TaskReportInterface::class);
        $builder = new AttachmentBuilder(
            'some-file.txt',
            $report,
            '/path/to/temp/directory',
            $filesystem,
            function (AttachmentBuffer $diagnostic, AttachmentBuilder $sender) use (&$builder) {
                $this->assertSame($builder, $sender);
                $this->assertAttachmentIs(
                    '/path/to/temp/directory/some-file.txt',
                    'some-file.txt',
                    'application/octet-stream',
                    $diagnostic
                );
            }
        );
        $builder->fromString('file contents')->setMimeType('application/octet-stream');

        $this->assertSame($report, $builder->end());
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testCallingEndCallsCallback(): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->getMock();

        $filesystem->expects($this->once())->method('dumpFile');

        $called = false;
        $report = $this->createMock(TaskReportInterface::class);
        $builder = new AttachmentBuilder(
            'some-file.txt',
            $report,
            '/path/to/temp/directory',
            $filesystem,
            function (AttachmentBuffer $buffer, AttachmentBuilder $sender) use (&$builder, &$called) {
                $this->assertSame($builder, $sender);
                $called = true;
            }
        );

        $builder->fromString('')->end();

        $this->assertTrue($called, 'Callback was not called.');
    }

    private function assertAttachmentIs(
        string $absolutePath,
        string $localName,
        ?string $mimeType,
        AttachmentBuffer $diagnostic
    ) {
        $this->assertStringStartsWith($absolutePath, $diagnostic->getAbsolutePath());
        $this->assertSame($localName, $diagnostic->getLocalName());
        $this->assertSame($mimeType, $diagnostic->getMimeType());
    }
}
