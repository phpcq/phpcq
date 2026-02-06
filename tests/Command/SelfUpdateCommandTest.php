<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Command;

use Generator;
use Phpcq\Runner\Command\SelfUpdateCommand;
use Phpcq\Runner\Downloader\DownloaderInterface;
use Phpcq\Runner\Test\WithConsecutiveTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function array_merge;
use function date;
use function file_get_contents;
use function file_put_contents;
use function getenv;
use function json_encode;
use function realpath;
use function sys_get_temp_dir;
use function tempnam;
use function uniqid;
use function unlink;

use const DATE_ATOM;

/** @covers \Phpcq\Runner\Command\SelfUpdateCommand */
final class SelfUpdateCommandTest extends TestCase
{
    use WithConsecutiveTrait;

    public static function provideUpdate(): Generator
    {
        yield 'New version found' => [
            'expectedOutput' => 'Version <info>"0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc"</info> installed.'
                . ' New version <info>"0.0.0.1-dev+2022-01-20-16-01-15-UTC-a4873bc"</info> available.',
            'installed'      => '0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc',
            'available'      => '0.0.0.1-dev+2022-01-20-16-01-15-UTC-a4873bc',
            'download'       => true,
            'force'          => false,
        ];

        yield 'Same version found' => [
            'expectedOutput' => 'Version <info>"0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc"</info> already installed.',
            'installed'    => '0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc',
            'available'    => '0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc',
            'download'       => false,
            'force'          => false,
        ];

        yield 'Older version found' => [
            'expectedOutput' => 'Installed version <info>"0.0.0.1-dev+2022-01-20-16-01-15-UTC-a4873bc"</info>'
                . ' is newer than available version <info>"0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc"</info>.',
            'installed' => '0.0.0.1-dev+2022-01-20-16-01-15-UTC-a4873bc',
            'available' => '0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc',
            'download'       => false,
            'force'          => false,
        ];

        yield 'Force download on same version' => [
            'expectedOutput' => 'Version <info>"0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc"</info> already installed.',
            'installed'    => '0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc',
            'available'    => '0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc',
            'download'       => true,
            'force'          => true,
        ];

        yield 'Force download on older version' => [
            'expectedOutput' => 'Installed version <info>"0.0.0.1-dev+2022-01-20-16-01-15-UTC-a4873bc"</info>'
                . ' is newer than available version <info>"0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc"</info>.',
            'installed' => '0.0.0.1-dev+2022-01-20-16-01-15-UTC-a4873bc',
            'available' => '0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc',
            'download'       => true,
            'force'          => true,
        ];
    }

    #[DataProvider('provideUpdate')]
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
        $downloader = $this->createMock(DownloaderInterface::class);
        $json = [
            'updated' => date(DATE_ATOM),
            'versions' => [
                [
                    'version' => $available,
                    'phar' => 'phpcq.phar',
                    'signature' => null,
                    'requirements' => [],
                ]
            ]
        ];
        $downloader
            ->expects($this->once())
            ->method('downloadJsonFile')
            ->with('https://phpcq.github.io/distrib/phpcq/unstable/versions.json')
            ->willReturn($json);

        if ($download) {
            $downloader
                ->expects($this->once())
                ->method('downloadFileTo')
                ->with('https://phpcq.github.io/distrib/phpcq/unstable/phpcq.phar')
                ->willReturnCallback(function () use ($downloadLocation): void {
                    file_put_contents($downloadLocation, 'PHPCQ DOWNLOAD');
                });
        }

        $output = $this->createMock(OutputInterface::class);
        $stubber = $output
            ->expects($download ? $this->exactly(2) : $this->once())
            ->method('writeln');

        if ($download) {
            $stubber->with(
                $this->callback(
                    $this->consecutiveCalls(
                        'Download phpcq.phar from https://phpcq.github.io/distrib/phpcq/unstable/phpcq.phar',
                        $expectedOutput
                    )
                )
            );
        } else {
            $stubber->with($expectedOutput);
        }

        $command = new SelfUpdateCommand($downloadLocation, $downloader);
        $command->setApplication($this->mockApplication($installed));
        $command->run($input, $output);

        if ($download) {
            self::assertFileExists($downloadLocation);
            self::assertSame('PHPCQ DOWNLOAD', file_get_contents($downloadLocation));
            unlink($downloadLocation);
        }
    }

    public static function provideDryRun(): Generator
    {
        yield 'New version found' => [
            'expectedOutput' => 'Version <info>"0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc"</info> installed.'
                . ' New version <info>"0.0.0.1-dev+2022-01-20-16-01-15-UTC-a4873bc"</info> available.',
            'installed' => '0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc',
            'available' => '0.0.0.1-dev+2022-01-20-16-01-15-UTC-a4873bc',
        ];

        yield 'Same version found' => [
            'expectedOutput' => 'Version <info>"0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc"</info> already installed.',
            'installed'    => '0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc',
            'available'    => '0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc',
        ];

        yield 'Older version found' => [
            'expectedOutput' => 'Installed version <info>"0.0.0.1-dev+2022-01-20-16-01-15-UTC-a4873bc"</info>'
                 . ' is newer than available version <info>"0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc"</info>.',
            'installed' => '0.0.0.1-dev+2022-01-20-16-01-15-UTC-a4873bc',
            'available' => '0.0.0.1-dev+2022-01-19-16-01-15-UTC-a4873bc',
        ];
    }

    #[DataProvider('provideDryRun')]
    public function testDryRun(
        string $expectedOutput,
        string $installed,
        string $available
    ): void {
        $input = $this->mockInput(
            [
                'dry-run' => true,
                'verbose' => null,
                'force'   => false,
                'unsigned' => true,
            ]
        );

        $json = [
            'updated' => date(DATE_ATOM),
            'versions' => [
                [
                    'version' => $available,
                    'phar' => 'phpcq.phar',
                    'signature' => null,
                    'requirements' => [],
                ]
            ]
        ];

        $downloader = $this->createMock(DownloaderInterface::class);
        $downloader
            ->expects($this->once())
            ->method('downloadJsonFile')
            ->with('https://phpcq.github.io/distrib/phpcq/unstable/versions.json')
            ->willReturn($json);

        $output = $this->createMock(OutputInterface::class);
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
        $input = $this->createMock(InputInterface::class);
        $input
            ->expects($this->atLeastOnce())
            ->method('getOption')
            ->willReturnCallback(
                fn(string $name) => $options[$name] ?? null
            );

        return $input;
    }

    private function mockApplication(string $version): Application
    {
        $helperSet   = $this->createMock(HelperSet::class);
        $application = $this->createPartialMock(Application::class, ['getHelperSet', 'getVersion']);
        $application->setDefaultCommand('run');
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
