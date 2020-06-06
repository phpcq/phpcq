<?php

declare(strict_types=1);

namespace Repository;

use Phpcq\Repository\InlineBootstrap;
use Phpcq\Repository\LockFileDumper;
use Phpcq\Repository\Repository;
use Phpcq\Repository\ToolHash;
use Phpcq\Repository\ToolInformation;
use PHPUnit\Framework\TestCase;

class LockFileDumperTest extends TestCase
{
    public function testLockFileDump(): void
    {
        $repository = new Repository();
        $repository->addVersion(new ToolInformation(
            'test',
            '1.0.0',
            'test.phar',
            [
                'php' => '>=7.3'
            ],
            new InlineBootstrap('1.0.0', '<?php return []; ?>'),
            new ToolHash(ToolHash::SHA_256, 'foo'),
            'test.phar.asc'
        ));
        $repository->addVersion(new ToolInformation(
            'test2',
            '1.0.0',
            'test2.phar',
            [
                'php' => '>=7.3'
            ],
            new InlineBootstrap('1.0.0', '<?php return [\'foo\']; ?>'),
            new ToolHash(ToolHash::SHA_256, 'foo'),
            'test2.phar.asc'
        ));

        $tempDir = sys_get_temp_dir();
        $fileName = tempnam($tempDir, 'phpcq-test');

        $dumper = new LockFileDumper($tempDir);
        $dumper->dump($repository, str_replace($tempDir . '/', '', $fileName));

        $data = json_decode(file_get_contents($fileName), true);
        $this->assertSame(
            [
                'bootstraps' => [
                    'bootstrap-0' => [
                        'plugin-version' => '1.0.0',
                        'type' => 'inline',
                        'code' => '<?php return []; ?>'
                    ],
                    'bootstrap-1' => [
                        'plugin-version' => '1.0.0',
                        'type' => 'inline',
                        'code' => '<?php return [\'foo\']; ?>'
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
