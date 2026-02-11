<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Signature;

use Phpcq\GnuPG\Exception\DownloadFailureException;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\Runner\Downloader\FileDownloader;
use Phpcq\Runner\Signature\SignatureFileDownloader;
use Phpcq\Runner\Test\WithConsecutiveTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

/** @covers \Phpcq\Runner\Signature\SignatureFileDownloader */
final class SignatureFileDownloaderTest extends TestCase
{
    use WithConsecutiveTrait;

    public function testDownloadFile(): void
    {
        $downloader = $this->getMockBuilder(FileDownloader::class);
        $downloader->disableOriginalConstructor();
        $downloader = $downloader->getMock();
        $downloader->expects($this->once())->method('downLoadFile')->willReturn('KEY');

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->exactly(2))
            ->method('writeln')
            ->with(
                $this->callback(
                    $this->consecutiveCalls(
                        'Downloading key from https://example.org/key',
                        'Key downloaded from https://example.org/key'
                    )
                ),
                $this->callback(
                    $this->consecutiveCalls(
                        OutputInterface::VERBOSITY_VERBOSE,
                        OutputInterface::VERBOSITY_VERBOSE,
                    )
                ),
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

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->exactly(2))->method('writeln')->with(
            $this->callback(
                $this->consecutiveCalls(
                    'Downloading key from https://example.org/key',
                    'Downloading key from https://example.org/key failed',
                )
            ),
            $this->callback(
                $this->consecutiveCalls(
                    OutputInterface::VERBOSITY_VERBOSE,
                    OutputInterface::VERBOSITY_VERBOSE
                )
            ),
        );

        $signatureDownloader = new SignatureFileDownloader($downloader, $output);

        $this->expectException(DownloadFailureException::class);
        $signatureDownloader->downloadFile('https://example.org/key');
    }
}
