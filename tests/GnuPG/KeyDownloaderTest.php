<?php

declare(strict_types=1);

namespace Phpcq\Test\GnuPG;

use Phpcq\Exception\DownloadGpgKeyFailedException;
use Phpcq\Exception\RuntimeException;
use Phpcq\FileDownloader;
use Phpcq\GnuPG\KeyDownloader;
use PHPUnit\Framework\TestCase;
use function sys_get_temp_dir;

final class KeyDownloaderTest extends TestCase
{
    public function testDownload() : void
    {
        $downloader = $this
            ->getMockBuilder(FileDownloader::class)
            ->onlyMethods(['downloadFile'])
            ->setConstructorArgs([sys_get_temp_dir() . '/phpcq', []])
            ->getMock();

        $downloader
            ->expects($this->exactly(1))
            ->method('downloadFile')
            ->withAnyParameters()
            ->willReturn('FOO');

        $keyDownloader = new KeyDownloader($downloader);
        $result = $keyDownloader->download('ABCDEF');

        $this->assertEquals('FOO', $result);
    }

    public function testTryMultipleServers() : void
    {
        $downloader = $this
            ->getMockBuilder(FileDownloader::class)
            ->onlyMethods(['downloadFile'])
            ->setConstructorArgs([sys_get_temp_dir() . '/phpcq', []])
            ->getMock();

        $invocationMocker = $downloader
            ->expects($this->exactly(2))
            ->method('downloadFile');

        $invocationMocker
            ->with($this->callback(function (string $url) use ($invocationMocker) {
                if ($url === 'https://server-2.org/pks/lookup?op=get&options=mr&search=0xABCDEF') {
                    $invocationMocker->willReturn('FOO');

                    return true;
                }

                $invocationMocker->willThrowException(new RuntimeException());

                return true;
            }))
            ->willThrowException(new RuntimeException());

        $keyDownloader = new KeyDownloader($downloader, ['server-1.org', 'server-2.org']);
        $result = $keyDownloader->download('ABCDEF');

        $this->assertEquals('FOO', $result);
    }

    public function testDownloadFailure() : void
    {
        $downloader = $this
            ->getMockBuilder(FileDownloader::class)
            ->onlyMethods(['downloadFile'])
            ->setConstructorArgs([sys_get_temp_dir() . '/phpcq', []])
            ->getMock();

        $downloader
            ->expects($this->exactly(4))
            ->method('downloadFile')
            ->withAnyParameters()
            ->willThrowException(new RuntimeException());

        $this->expectException(DownloadGpgKeyFailedException::class);

        $keyDownloader = new KeyDownloader($downloader);
        $keyDownloader->download('ABCDEF');
    }
}
