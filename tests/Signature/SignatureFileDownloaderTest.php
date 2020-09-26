<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Signature;

use Phpcq\GnuPG\Exception\DownloadFailureException;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\Runner\FileDownloader;
use Phpcq\Runner\Signature\SignatureFileDownloader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

/** @covers \Phpcq\Runner\Signature\SignatureFileDownloader */
final class SignatureFileDownloaderTest extends TestCase
{
    public function testDownloadFile(): void
    {
        $downloader = $this->getMockBuilder(FileDownloader::class);
        $downloader->disableOriginalConstructor();
        $downloader = $downloader->getMock();
        $downloader->expects($this->once())->method('downLoadFile')->willReturn('KEY');

        $output = $this->getMockForAbstractClass(OutputInterface::class);
        $output->expects($this->exactly(2))->method('writeln')->withConsecutive(
            ['Downloading key from https://example.org/key', OutputInterface::VERBOSITY_VERBOSE],
            ['Key downloaded from https://example.org/key', OutputInterface::VERBOSITY_VERBOSE],
        );

        $signatureDownloader = new SignatureFileDownloader($downloader, $output);
        $this->assertSame('KEY', $signatureDownloader->downloadFile('https://example.org/key'));
    }

    public function testDownloadFailed(): void
    {
        $downloader = $this->getMockBuilder(FileDownloader::class);
        $downloader->disableOriginalConstructor();
        $downloader = $downloader->getMock();
        $downloader->expects($this->once())->method('downLoadFile')->willThrowException(new RuntimeException());

        $output = $this->getMockForAbstractClass(OutputInterface::class);
        $output->expects($this->exactly(2))->method('writeln')->withConsecutive(
            ['Downloading key from https://example.org/key', OutputInterface::VERBOSITY_VERBOSE],
            ['Downloading key from https://example.org/key failed', OutputInterface::VERBOSITY_VERBOSE],
        );

        $signatureDownloader = new SignatureFileDownloader($downloader, $output);

        $this->expectException(DownloadFailureException::class);
        $signatureDownloader->downloadFile('https://example.org/key');
    }
}
