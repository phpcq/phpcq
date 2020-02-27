<?php

declare(strict_types=1);

namespace Phpcq\Test\Repository;

use Phpcq\Exception\RuntimeException;
use Phpcq\FileDownloader;
use Phpcq\Repository\RemoteBootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Repository\RemoteBootstrap
 */
class RemoteBootstrapTest extends TestCase
{
    public function testGetters(): void
    {
        $downloader = $this->createMock(FileDownloader::class);
        $downloader->expects($this->once())->method('downloadFile')->with('the url')->willReturn('the code');

        $instance = new RemoteBootstrap('1.0.0', 'the url', $downloader, '');
        $this->assertSame('1.0.0', $instance->getPluginVersion());
        $this->assertSame('the code', $instance->getCode());
    }

    public function testThrowsForInvalidVersion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid version string: 11.0.0');
        new RemoteBootstrap('11.0.0', '', $this->createMock(FileDownloader::class), '');
    }
}