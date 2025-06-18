<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\SelfUpdate;

use Phpcq\Runner\Downloader\DownloaderInterface;
use Phpcq\Runner\Platform\PlatformRequirementChecker;
use Phpcq\Runner\SelfUpdate\VersionsRepositoryLoader;
use PHPUnit\Framework\TestCase;

use function date;

/** @covers \Phpcq\Runner\SelfUpdate\VersionsRepositoryLoader */
final class VersionsRepositoryLoaderTest extends TestCase
{
    public function testLoad(): void
    {
        $downloader = $this->getMockForAbstractClass(DownloaderInterface::class);
        $downloader->method('downloadJsonFile')->willReturn(
            [
                'updated' => date(DATE_ATOM),
                'versions' => [
                    [
                        'version' => '1.0.0',
                        'phar' => 'test.phar',
                        'signature' => 'test.phar.asc',
                        'requirements' => []
                    ]
                ]
            ]
        );

        $loader = new VersionsRepositoryLoader(PlatformRequirementChecker::create(), $downloader);
        $repository = $loader->load('test.json');
        $version = $repository->findMatchingVersion();

        self::assertSame('1.0.0', $version->getVersion());
    }

    public function testMatchingLatest(): void
    {
        $downloader = $this->getMockForAbstractClass(DownloaderInterface::class);
        $downloader->method('downloadJsonFile')->willReturn(
            [
                'updated' => date(DATE_ATOM),
                'versions' => [
                    [
                        'version' => '1.0.0',
                        'phar' => 'test.phar',
                        'signature' => 'test.phar.asc',
                        'requirements' => []
                    ],
                    [
                        'version' => '2.0.0',
                        'phar' => 'test.phar',
                        'signature' => 'test.phar.asc',
                        'requirements' => []
                    ]
                ]
            ]
        );

        $loader = new VersionsRepositoryLoader(PlatformRequirementChecker::create(), $downloader);
        $repository = $loader->load('test.json');
        $version = $repository->findMatchingVersion();

        self::assertSame('2.0.0', $version->getVersion());
    }

    public function testMatchingSigned(): void
    {
        $downloader = $this->getMockForAbstractClass(DownloaderInterface::class);
        $downloader->method('downloadJsonFile')->willReturn(
            [
                'updated' => date(DATE_ATOM),
                'versions' => [
                    [
                        'version' => '1.0.0',
                        'phar' => 'test.phar',
                        'signature' => 'test.phar.asc',
                        'requirements' => []
                    ],
                    [
                        'version' => '2.0.0',
                        'phar' => 'test.phar',
                        'signature' => null,
                        'requirements' => []
                    ]
                ]
            ]
        );

        $loader = new VersionsRepositoryLoader(PlatformRequirementChecker::create(), $downloader);
        $repository = $loader->load('test.json');
        $version = $repository->findMatchingVersion(null, true);

        self::assertSame('1.0.0', $version->getVersion());
    }

    public function testMatchingRequirements(): void
    {
        $downloader = $this->getMockForAbstractClass(DownloaderInterface::class);
        $downloader->method('downloadJsonFile')->willReturn(
            [
                'updated' => date(DATE_ATOM),
                'versions' => [
                    [
                        'version' => '1.0.0',
                        'phar' => 'test.phar',
                        'signature' => 'test.phar.asc',
                        'requirements' => [
                            'php' => '^7.4 || ^8.0',
                        ]
                    ],
                    [
                        'version' => '2.0.0',
                        'phar' => 'test.phar',
                        'signature' => 'test.phar.asc',
                        'requirements' => [
                            'php' => '^4.1',
                        ]
                    ],
                    [
                        'version' => '3.0.0',
                        'phar' => 'test.phar',
                        'signature' => 'test.phar.asc',
                        'requirements' => [
                            'ext-NON-EXISTING' => '*',
                        ]
                    ],
                ]
            ]
        );

        $loader = new VersionsRepositoryLoader(PlatformRequirementChecker::create(), $downloader);
        $repository = $loader->load('test.json');
        $version = $repository->findMatchingVersion();

        self::assertSame('1.0.0', $version->getVersion());
    }
}
