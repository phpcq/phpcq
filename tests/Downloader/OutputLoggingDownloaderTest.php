<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Downloader;

use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\Runner\Downloader\DownloaderInterface;
use Phpcq\Runner\Downloader\OutputLoggingDownloader;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Downloader\OutputLoggingDownloader */
final class OutputLoggingDownloaderTest extends TestCase
{
    public function testDownloadFileTo(): void
    {
        $downloader = $this->createMock(DownloaderInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $downloader
            ->expects($this->once())
            ->method('downloadFileTo')
            ->with('https://foo.bar', '/tmp/foo.bar', '', false);

        $output
            ->expects($this->once())
            ->method('writeln')
            ->with('Downloading https://foo.bar to /tmp/foo.bar', OutputInterface::VERBOSITY_DEBUG);

        $instance = new OutputLoggingDownloader($downloader, $output);
        $instance->downloadFileTo('https://foo.bar', '/tmp/foo.bar');
    }

    public function testDownloadFile(): void
    {
        $downloader = $this->createMock(DownloaderInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $downloader
            ->expects($this->once())
            ->method('downloadFile')
            ->with('https://foo.bar', '', false);

        $output
            ->expects($this->once())
            ->method('writeln')
            ->with('Downloading https://foo.bar', OutputInterface::VERBOSITY_DEBUG);

        $instance = new OutputLoggingDownloader($downloader, $output);
        $instance->downloadFile('https://foo.bar');
    }

    public function testJsonDownloadFile(): void
    {
        $downloader = $this->createMock(DownloaderInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $downloader
            ->expects($this->once())
            ->method('downloadJsonFile')
            ->with('https://foo.bar', '', false);

        $output
            ->expects($this->once())
            ->method('writeln')
            ->with('Downloading json file https://foo.bar', OutputInterface::VERBOSITY_DEBUG);

        $instance = new OutputLoggingDownloader($downloader, $output);
        $instance->downloadJsonFile('https://foo.bar');
    }
}
