<?php

declare(strict_types=1);

namespace Phpcq;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\FileDownloader
 */
class FileDownloaderTest extends TestCase
{
    public function testDownloadJsonFile(): void
    {
        $downloader = $this
            ->getMockBuilder(FileDownloader::class)
            ->onlyMethods(['downloadFile'])
            ->setConstructorArgs([sys_get_temp_dir() . '/phpcq', []])
            ->getMock();
        $downloader
            ->expects($this->once())
            ->method('downloadFile')
            ->with('some/url')
            ->willReturn('{"json": "file"}');

        $this->assertSame(['json' => 'file'], $downloader->downloadJsonFile('some/url'));
    }

    public function testDownloadFileTo(): void
    {
        $downloader = $this
            ->getMockBuilder(FileDownloader::class)
            ->onlyMethods(['downloadFile'])
            ->setConstructorArgs([sys_get_temp_dir() . '/phpcq', []])
            ->getMock();
        $downloader
            ->expects($this->once())
            ->method('downloadFile')
            ->with('some/url')
            ->willReturn('{"json": "file"}');

        $filename = sys_get_temp_dir() . '/' . uniqid('test-download');
        try {
            $downloader->downloadFileTo('some/url', $filename);
            $this->assertFileExists($filename);
            $this->assertSame('{"json": "file"}', file_get_contents($filename));
        } finally {
            unlink($filename);
        }
    }
}