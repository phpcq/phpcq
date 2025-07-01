<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Runner\Repository;

use Phpcq\Runner\Downloader\FileDownloader;
use Phpcq\Runner\Platform\PlatformRequirementCheckerInterface;
use Phpcq\Runner\Repository\DownloadingJsonFileLoader;
use Phpcq\Runner\Repository\JsonRepositoryLoader;
use Phpcq\Runner\Test\TemporaryFileProducingTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Repository\JsonRepositoryLoader
 */
final class JsonRepositoryLoaderTest extends TestCase
{
    use TemporaryFileProducingTestTrait;

    public function testLoadRepository(): void
    {
        $downloader = new FileDownloader(self::$tempdir . '/phpcq-test');
        $requirementChecker = $this->createMock(PlatformRequirementCheckerInterface::class);
        $requirementChecker
            ->method('isFulfilled')
            ->with('php', '^7.1')
            ->willReturn(true);

        $loader = new JsonRepositoryLoader($requirementChecker, new DownloadingJsonFileLoader($downloader));
        $repository = $loader->loadFile(__DIR__ . '/../fixtures/repositories/repository.json');

        // Test the included version exists.
        $this->assertTrue($repository->hasToolVersion('phpmd', '2.8.2'));
        $version = $repository->getToolVersion('phpmd', '2.8.2');

        $this->assertSame('2.8.2', $version->getVersion());
        $this->assertSame('https://github.com/phpmd/phpmd/releases/download/2.8.2/phpmd.phar', $version->getPharUrl());

        $this->assertTrue($repository->hasPluginVersion('phpmd', '1.0.0'));
        $version = $repository->getPluginVersion('phpmd', '1.0.0');
        $this->assertSame('1.0.0', $version->getVersion());
        $this->assertSame('https://example.org/foo.php', $version->getFilePath());
    }
}
