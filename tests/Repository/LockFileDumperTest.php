<?php

declare(strict_types=1);

namespace Phpcq\Test\Repository;

use Phpcq\Repository\InlineBootstrap;
use Phpcq\Runner\Repository\LockFileDumper;
use Phpcq\Runner\Repository\Repository;
use Phpcq\Repository\ToolHash;
use Phpcq\Repository\ToolInformation;
use Phpcq\Test\TemporaryFileProducingTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Repository\LockFileDumper
 */
class LockFileDumperTest extends TestCase
{
    use TemporaryFileProducingTestTrait;

    public function testLockFileDump(): void
    {
        $repository = new Repository();
        $repository->addPluginVersion(new ToolInformation(
            'test',
            '1.0.0',
            'test.phar',
            [
                'php' => '>=7.3'
            ],
            new InlineBootstrap('1.0.0', '<?php return []; ?>', null),
            new ToolHash(ToolHash::SHA_256, 'foo'),
            'test.phar.asc'
        ));
        $repository->addPluginVersion(new ToolInformation(
            'test2',
            '1.0.0',
            'test2.phar',
            [
                'php' => '>=7.3'
            ],
            new InlineBootstrap('1.0.0', '<?php return [\'foo\']; ?>', null),
            new ToolHash(ToolHash::SHA_256, 'foo'),
            'test2.phar.asc'
        ));

        $fileName = tempnam(self::$tempdir, 'phpcq-test');

        $dumper = new LockFileDumper(self::$tempdir);
        $dumper->dump($repository, str_replace(self::$tempdir . '/', '', $fileName));

        $data = json_decode(file_get_contents($fileName), true);
        $this->assertSame(
            [
                'bootstraps' => [
                    'bootstrap-0' => [
                        'plugin-version' => '1.0.0',
                        'type' => 'inline',
                        'code' => '<?php return []; ?>',
                        'hash' => null,
                    ],
                    'bootstrap-1' => [
                        'plugin-version' => '1.0.0',
                        'type' => 'inline',
                        'code' => '<?php return [\'foo\']; ?>',
                        'hash' => null,
                    ],
                ],
                'phars' => [
                    'test' => [
                        [
                            'version'      => '1.0.0',
                            'phar-url'     => 'test.phar',
                            'bootstrap'    => 'bootstrap-0',
                            'requirements' => [
                                'php' => '>=7.3'
                            ],
                            'hash' => [
                                'type'  => 'sha-256',
                                'value' => 'foo'
                            ],
                            'signature'    => 'test.phar.asc'
                        ],
                    ],
                    'test2' => [
                        [
                            'version'      => '1.0.0',
                            'phar-url'     => 'test2.phar',
                            'bootstrap'    => 'bootstrap-1',
                            'requirements' => [
                                'php' => '>=7.3'
                            ],
                            'hash' => [
                                'type'  => 'sha-256',
                                'value' => 'foo'
                            ],
                            'signature'    => 'test2.phar.asc'
                        ]
                    ]
                ]
            ],
            $data
        );

        unlink($fileName);
    }
}
