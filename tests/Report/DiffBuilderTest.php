<?php

declare(strict_types=1);

namespace Phpcq\Test\Report;

use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Report\ToolReportInterface;
use Phpcq\Report\DiffBuilder;
use Phpcq\Report\Buffer\DiffBuffer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/** @covers \Phpcq\Report\DiffBuilder */
class DiffBuilderTest extends TestCase
{
    public function testCallingEndThrowsWhenNoDataHasBeenAdded(): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->getMock();

        $filesystem->expects($this->never())->method('dumpFile');

        $report  = $this->getMockForAbstractClass(ToolReportInterface::class);
        $builder = new DiffBuilder(
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

        $report  = $this->getMockForAbstractClass(ToolReportInterface::class);
        $builder = new DiffBuilder(
            'some-file.txt',
            $report,
            '/path/to/temp/directory',
            $filesystem,
            function (DiffBuffer $diagnostic, DiffBuilder $sender) use (&$builder) {
                $this->assertSame($builder, $sender);
                $this->assertDiffIs(
                    '/path/to/other/file',
                    'some-file.txt',
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

        $report  = $this->getMockForAbstractClass(ToolReportInterface::class);
        $builder = new DiffBuilder(
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

        $report  = $this->getMockForAbstractClass(ToolReportInterface::class);
        $builder = new DiffBuilder(
            'some-file.txt',
            $report,
            '/path/to/temp/directory',
            $filesystem,
            function (DiffBuffer $diagnostic, DiffBuilder $sender) use (&$builder) {
                $this->assertSame($builder, $sender);
                $this->assertDiffIs(
                    '/path/to/temp/directory/some-file.txt',
                    'some-file.txt',
                    $diagnostic
                );
            }
        );
        $builder->fromString('file contents')->end();

        $this->assertSame($report, $builder->end());
    }

    /** @SuppressWarnings(PHPMD.UnusedLocalVariable) */
    public function testCallingEndCallsCallback(): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->getMock();

        $filesystem->expects($this->once())->method('dumpFile');

        $called = false;
        $report = $this->getMockForAbstractClass(ToolReportInterface::class);
        $builder = new DiffBuilder(
            'some-file.txt',
            $report,
            '/path/to/temp/directory',
            $filesystem,
            function (DiffBuffer $buffer, DiffBuilder $sender) use (&$builder, &$called) {
                $this->assertSame($builder, $sender);
                $called = true;
            }
        );

        $builder->fromString('')->end();

        $this->assertTrue($called, 'Callback was not called.');
    }

    private function assertDiffIs(string $absolutePath, string $localName, DiffBuffer $diagnostic)
    {
        $this->assertStringStartsWith($absolutePath, $diagnostic->getAbsolutePath());
        $this->assertSame($localName, $diagnostic->getLocalName());
    }
}
