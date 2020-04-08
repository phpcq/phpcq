<?php

declare(strict_types=1);

namespace Phpcq\Test\Command;

use Phpcq\Command\ExecCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Command\ExecCommand
 */
class ExecCommandTest extends TestCase
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
        ];
    }

    /**
     * @dataProvider prepareProvider
     */
    public function testPreparesInputCorrectly(array $expected, array $argv): void
    {
        $this->assertSame($expected, ExecCommand::prepare($argv));
    }
}
