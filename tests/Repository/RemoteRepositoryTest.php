<?php

declare(strict_types=1);

namespace Phpcq\Test\Repository;

use Phpcq\FileDownloader;
use Phpcq\Repository\JsonRepositoryLoader;
use Phpcq\Repository\RemoteRepository;
use Phpcq\Repository\ToolInformationInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Repository\RemoteRepository
 */
class RemoteRepositoryTest extends TestCase
{
    public function testAddsVersionAndCanRetrieveVersion(): void
    {
        $downloader = $this->createMock(FileDownloader::class);
        $loader     = new JsonRepositoryLoader($downloader);
        $repository = new RemoteRepository('http://dummy/repository.json', $loader);

        $downloader
            ->expects($this->once())
            ->method('downloadJsonFile')
            ->with('http://dummy/repository.json')
            ->willReturn(['phars' => [
                'supertool' => [
                    [
                        'version'   => '1.2.0',
                        'phar-url'  => 'http://dummy/supertool-1.2.0.phar',
                        'bootstrap' => [
                            'plugin-version' => '1.0.0',
                            'type'           => 'inline',
                            'code'           => 'bootstrap-code'
                        ],
                    ]
                ]
            ]]);

        $this->assertTrue($repository->hasTool('supertool', '1.2.0'));
        $this->assertTrue($repository->hasTool('supertool', '^1.0.0'));
        $version = $repository->getTool('supertool', '^1.0.0');

        $this->assertSame('supertool', $version->getName());
        $this->assertSame('1.2.0', $version->getVersion());
        $this->assertSame('http://dummy/supertool-1.2.0.phar', $version->getPharUrl());
        $this->assertSame('1.0.0', $version->getBootstrap()->getPluginVersion());
        $this->assertSame('bootstrap-code', $version->getBootstrap()->getCode());
    }

    public function testEnumeratesAllVersions(): void
    {
        $downloader = $this->createMock(FileDownloader::class);
        $loader     = new JsonRepositoryLoader($downloader);
        $repository = new RemoteRepository('http://dummy/repository.json', $loader);

        $downloader
            ->expects($this->once())
            ->method('downloadJsonFile')
            ->with('http://dummy/repository.json')
            ->willReturn(['phars' => [
                'supertool' => [
                    [
                        'version'   => '1.2.0',
                        'phar-url'  => 'http://dummy/supertool-1.2.0.phar',
                        'bootstrap' => [
                            'plugin-version' => '1.0.0',
                            'type'           => 'inline',
                            'code'           => 'bootstrap-code'
                        ],
                    ],
                    [
                        'version'   => '1.3.0',
                        'phar-url'  => 'http://dummy/supertool-1.3.0.phar',
                        'bootstrap' => [
                            'plugin-version' => '1.0.0',
                            'type'           => 'inline',
                            'code'           => 'bootstrap-code2'
                        ],
                    ],
                ],
            ]]);

        $versions = iterator_to_array($repository->getIterator());

        $this->assertSame('supertool', $versions[0]->getName());
        $this->assertSame('1.2.0', $versions[0]->getVersion());
        $this->assertSame('http://dummy/supertool-1.2.0.phar', $versions[0]->getPharUrl());
        $this->assertSame('1.0.0', $versions[0]->getBootstrap()->getPluginVersion());
        $this->assertSame('bootstrap-code', $versions[0]->getBootstrap()->getCode());

        $this->assertSame('supertool', $versions[1]->getName());
        $this->assertSame('1.3.0', $versions[1]->getVersion());
        $this->assertSame('http://dummy/supertool-1.3.0.phar', $versions[1]->getPharUrl());
        $this->assertSame('1.0.0', $versions[1]->getBootstrap()->getPluginVersion());
        $this->assertSame('bootstrap-code2', $versions[1]->getBootstrap()->getCode());
    }
}