<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Command;

use Generator;
use Phpcq\Runner\Command\SelfUpdateCommand;
use Phpcq\Runner\Downloader\DownloaderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function array_merge;
use function file_get_contents;
use function file_put_contents;
use function getenv;
use function realpath;
use function sys_get_temp_dir;
use function tempnam;
use function uniqid;
use function unlink;

/** @covers \Phpcq\Runner\Command\SelfUpdateCommand */
final class SelfUpdateCommandTest extends TestCase
{
    public function provideUpdate(): Generator
    {
        yield 'New version found' => [
            'expectedOutput' => 'Version <info>"0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc"</info> installed.'
                . ' New version <info>"0.0.0.1-dev-2022-01-20-16-01-15-UTC-a4873bc"</info> available.',
            'installed'      => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
            'available'      => '0.0.0.1-dev-2022-01-20-16-01-15-UTC-a4873bc',
            'download'       => true,
            'force'          => false,
        ];

        yield 'Same version found' => [
            'expectedOutput' => 'Version <info>"0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc"</info> already installed.',
            'installed'    => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
            'available'    => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
            'download'       => false,
            'force'          => false,
        ];

        yield 'Older version found' => [
            'expectedOutput' => 'Installed version <info>"0.0.0.1-dev-2022-01-20-16-01-15-UTC-a4873bc"</info>'
                . ' is newer than available version <info>"0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc"</info>.',
            'installed' => '0.0.0.1-dev-2022-01-20-16-01-15-UTC-a4873bc',
            'available' => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
            'download'       => false,
            'force'          => false,
        ];

        yield 'Force download on same version' => [
            'expectedOutput' => 'Version <info>"0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc"</info> already installed.',
            'installed'    => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
            'available'    => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
            'download'       => true,
            'force'          => true,
        ];

        yield 'Force download on older version' => [
            'expectedOutput' => 'Installed version <info>"0.0.0.1-dev-2022-01-20-16-01-15-UTC-a4873bc"</info>'
                . ' is newer than available version <info>"0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc"</info>.',
            'installed' => '0.0.0.1-dev-2022-01-20-16-01-15-UTC-a4873bc',
            'available' => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
            'download'       => true,
            'force'          => true,
        ];
    }

    /** @dataProvider provideUpdate */
    public function testUpdate(
        string $expectedOutput,
        string $installed,
        string $available,
        bool $download,
        bool $force
    ): void {
        $input = $this->mockInput(
            [
                'dry-run'  => false,
                'unsigned' => true,
                'verbose'  => null,
                'force'    => $force
            ]
        );

        $downloadLocation = tempnam(sys_get_temp_dir(), 'phpcq.test.phar');
        $downloader = $this->getMockForAbstractClass(DownloaderInterface::class);
        $downloader
            ->expects($this->once())
            ->method('downloadFile')
            ->with('https://phpcq.github.io/distrib/phpcq/unstable/current.txt')
            ->willReturn('phpcq ' . $available);

        if ($download) {
            $downloader
                ->expects($this->once())
                ->method('downloadFileTo')
                ->with('https://phpcq.github.io/distrib/phpcq/unstable/phpcq.phar')
                ->willReturnCallback(function () use ($downloadLocation): void {
                    file_put_contents($downloadLocation, 'PHPCQ DOWNLOAD');
                });
        }

        $output = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects($download ? $this->exactly(2) : $this->once())
            ->method('writeln')
            ->withConsecutive([$expectedOutput]);

        $command = new SelfUpdateCommand($downloadLocation, $downloader);
        $command->setApplication($this->mockApplication($installed));
        $command->run($input, $output);

        if ($download) {
            self::assertFileExists($downloadLocation);
            self::assertSame('PHPCQ DOWNLOAD', file_get_contents($downloadLocation));
            unlink($downloadLocation);
        }
    }

    public function provideDryRun(): Generator
    {
        yield 'New version found' => [
            'expectedOutput' => 'Version <info>"0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc"</info> installed.'
                . ' New version <info>"0.0.0.1-dev-2022-01-20-16-01-15-UTC-a4873bc"</info> available.',
            'installed' => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
            'available' => '0.0.0.1-dev-2022-01-20-16-01-15-UTC-a4873bc',
        ];

        yield 'Same version found' => [
            'expectedOutput' => 'Version <info>"0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc"</info> already installed.',
            'installed'    => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
            'available'    => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
        ];

        yield 'Older version found' => [
            'expectedOutput' => 'Installed version <info>"0.0.0.1-dev-2022-01-20-16-01-15-UTC-a4873bc"</info>'
                 . ' is newer than available version <info>"0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc"</info>.',
            'installed' => '0.0.0.1-dev-2022-01-20-16-01-15-UTC-a4873bc',
            'available' => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
        ];
    }

    /** @dataProvider provideDryRun */
    public function testDryRun(
        string $expectedOutput,
        string $installed,
        string $available
    ): void {
        $input = $this->mockInput(
            [
                'dry-run' => true,
                'verbose' => null,
            ]
        );

        $downloader = $this->getMockForAbstractClass(DownloaderInterface::class);
        $downloader
            ->expects($this->once())
            ->method('downloadFile')
            ->with('https://phpcq.github.io/distrib/phpcq/unstable/current.txt')
            ->willReturn('phpcq ' . $available);

        $output = $this->getMockForAbstractClass(OutputInterface::class);
        $output
            ->expects($this->once())
            ->method('writeln')
            ->with($expectedOutput);

        $downloadLocation = tempnam(sys_get_temp_dir(), 'phpcq.test.phar');
        $command = new SelfUpdateCommand($downloadLocation, $downloader);
        $command->setApplication($this->mockApplication($installed));
        $command->run($input, $output);
    }

    private function mockInput(array $options = []): InputInterface
    {
        $options = array_merge(
            [
                'home-dir' => sys_get_temp_dir() . '/' . uniqid('phpcq-'),
                'cache'    => (getenv('HOME') ?: sys_get_temp_dir()) . '/.cache/phpcq',
                'config'   => realpath(__DIR__ . '/../fixtures/phpcq-demo.yaml'),
                'base-uri' => 'https://phpcq.github.io/distrib/phpcq',
                'channel'  => 'unstable'
            ],
            $options
        );
        $input = $this->getMockForAbstractClass(InputInterface::class);
        $input
            ->expects($this->atLeastOnce())
            ->method('getOption')
            ->willReturnCallback(
                function (string $name) use ($options) {
                    return $options[$name] ?? null;
                }
            );

        return $input;
    }

    private function mockApplication(string $version): Application
    {
        $helperSet   = $this->createMock(HelperSet::class);
        $application = $this->createPartialMock(Application::class, ['getHelperSet', 'getVersion']);
        $application
            ->expects($this->once())
            ->method('getHelperSet')
            ->willReturn($helperSet);
        $application
            ->expects($this->once())
            ->method('getVersion')
            ->willReturn($version);

        return $application;
    }
}
