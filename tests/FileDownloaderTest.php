<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test;

use Phpcq\Runner\Downloader\FileDownloader;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Downloader\FileDownloader
 */
class FileDownloaderTest extends TestCase
{
    use TemporaryFileProducingTestTrait;

    public function testDownloadJsonFile(): void
    {
        $downloader = $this
            ->getMockBuilder(FileDownloader::class)
            ->onlyMethods(['downloadFile'])
            ->setConstructorArgs([self::$tempdir . '/phpcq', []])
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
            ->setConstructorArgs([self::$tempdir . '/phpcq', []])
            ->getMock();
        $downloader
            ->expects($this->once())
            ->method('downloadFile')
            ->with('some/url')
            ->willReturn('{"json": "file"}');

        $filename = self::$tempdir . '/' . uniqid('test-download');
        try {
            $downloader->downloadFileTo('some/url', $filename);
            $this->assertFileExists($filename);
            $this->assertSame('{"json": "file"}', file_get_contents($filename));
        } finally {
            unlink($filename);
        }
    }

    public function testDownloadFile(): void
    {
        $downloader = new FileDownloader(self::$tempdir);

        $filename = self::$tempdir . '/' . uniqid('test-download');
        file_put_contents($filename, '{"json": "file"}');

        $this->assertSame('{"json": "file"}', $downloader->downloadFile($filename));

        file_put_contents($filename, '{"json": "foo"}');
        $this->assertSame('{"json": "foo"}', $downloader->downloadFile($filename));

        $this->assertSame('{"json": "foo"}', $downloader->downloadFile($filename, '', true));
    }
}
