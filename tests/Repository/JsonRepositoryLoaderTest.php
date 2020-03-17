<?php

declare(strict_types=1);

namespace Phpcq\Test\Repository;

use Phpcq\Exception\RuntimeException;
use Phpcq\FileDownloader;
use Phpcq\Platform\PlatformInformationInterface;
use Phpcq\Repository\JsonRepositoryLoader;
use Phpcq\Repository\RemoteBootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Phpcq\Repository\JsonRepositoryLoader
 */
class JsonRepositoryLoaderTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        $filesystem = new Filesystem();
        $filesystem->remove(sys_get_temp_dir() . '/phpcq-test');
    }

    public function testInvalidRepositoryThrows(): void
    {
        $fixture = __DIR__ . '/../fixtures/repositories/invalid/broken-url.json';
        $platformInformation = $this->createMock(PlatformInformationInterface::class);
        $downloader = $this
            ->getMockBuilder(FileDownloader::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['downloadFile'])
            ->getMock();

        $downloader
            ->expects($this->exactly(2))
            ->method('downloadFile')
            ->withConsecutive([$fixture], ['123'])
            ->willReturnCallback(function ($url) use ($fixture) {
                switch ($url) {
                    case $fixture:
                        return file_get_contents($fixture);
                        break;
                }
                throw new RuntimeException('Invalid URI passed: ' . $url);
            });

        $loader = new JsonRepositoryLoader($platformInformation, $downloader);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid URI passed: 123');

        $loader->loadFile($fixture);
    }

    public function testLoadRepository()
    {
        $downloader = new FileDownloader(sys_get_temp_dir() . '/phpcq-test');
        $platformInformation = $this->createMock(PlatformInformationInterface::class);
        $platformInformation
            ->method('getInstalledVersion')
            ->willReturnArgument('php')
            ->willReturn('5.6.1');

        $loader = new JsonRepositoryLoader($platformInformation, $downloader);
        $repository = $loader->loadFile(__DIR__ . '/../fixtures/repositories/repository.json');

        // Test the included version exists.
        $this->assertTrue($repository->hasTool('phpmd', '2.8.2'));
        $version = $repository->getTool('phpmd', '2.8.2');

        $this->assertSame('2.8.2', $version->getVersion());
        $this->assertSame('https://github.com/phpmd/phpmd/releases/download/2.8.2/phpmd.phar', $version->getPharUrl());
        $this->assertInstanceOf(RemoteBootstrap::class, $version->getBootstrap());
        $this->assertSame('1.0.0', $version->getBootstrap()->getPluginVersion());
    }
}
