<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Command;

use Phpcq\Runner\Command\ExecCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Command\ExecCommand
 */
final class ExecCommandTest extends TestCase
{
    public function prepareProvider(): array
    {
        return [
            'missing tool name' => [
                'expected' => ['/path/to/phpcq', 'exec'],
                'argv'     => ['/path/to/phpcq', 'exec'],
            ],
            'plain exec command' => [
                'expected' => ['/path/to/phpcq', 'exec', '--', 'phpunit'],
                'argv'     => ['/path/to/phpcq', 'exec', 'phpunit'],
            ],
            'phpcq options' => [
                'expected' => ['/path/to/phpcq', 'exec', '-q', '--', 'phpunit'],
                'argv'     => ['/path/to/phpcq', 'exec', '-q', 'phpunit'],
            ],
            'prefix options for phpcq and suffix option(s) for tool' => [
                'expected' => ['/path/to/phpcq', 'exec', '-q', '--', 'phpunit', '--help'],
                'argv'     => ['/path/to/phpcq', 'exec', '-q', 'phpunit', '--help'],
            ],
            '"--" already provided' => [
                'expected' => ['/path/to/phpcq', 'exec', '-q', '--', 'phpunit', '--help'],
                'argv'     => ['/path/to/phpcq', 'exec', '-q', '--', 'phpunit', '--help'],
            ],
            'application options and missing tool name' => [
                'expected' => ['/path/to/phpcq', '--no-ansi', 'exec'],
                'argv'     => ['/path/to/phpcq', '--no-ansi', 'exec'],
            ],
            'application options and plain exec command' => [
                'expected' => ['/path/to/phpcq', '--no-ansi', 'exec', '--', 'phpunit'],
                'argv'     => ['/path/to/phpcq', '--no-ansi', 'exec', 'phpunit'],
            ],
            'application options and phpcq options' => [
                'expected' => ['/path/to/phpcq', '--no-ansi', 'exec', '-q', '--', 'phpunit'],
                'argv'     => ['/path/to/phpcq', '--no-ansi', 'exec', '-q', 'phpunit'],
            ],
            'application options and prefix options for phpcq and suffix option(s) for tool' => [
                'expected' => ['/path/to/phpcq', '--no-ansi', 'exec', '-q', '--', 'phpunit', '--help'],
                'argv'     => ['/path/to/phpcq', '--no-ansi', 'exec', '-q', 'phpunit', '--help'],
            ],
            'application options and "--" already provided' => [
                'expected' => ['/path/to/phpcq', '--no-ansi', 'exec', '-q', '--', 'phpunit', '--help'],
                'argv'     => ['/path/to/phpcq', '--no-ansi', 'exec', '-q', '--', 'phpunit', '--help'],
            ],
        ];
    }

    /**
     * @dataProvider prepareProvider
     */
    public function testPreparesInputCorrectly(array $expected, array $argv): void
    {
        $this->assertSame($expected, ExecCommand::prepareArguments($argv));
    }
}
