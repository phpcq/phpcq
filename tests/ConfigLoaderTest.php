<?php

declare(strict_types=1);

use Phpcq\ConfigLoader;
use Phpcq\Exception\InvalidConfigException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\ConfigLoader
 */
final class ConfigLoaderTest extends TestCase
{
    public function testFullFeaturedConfigFile(): void
    {
        $loader = new ConfigLoader(__DIR__ . '/fixtures/phpcq-demo.yaml');
        $config = $loader->getConfig();

        $this->assertArrayHasKey('directories', $config);
        $this->assertEquals(['src', 'tests'], $config['directories']);

        $this->assertArrayHasKey('repositories', $config);
        $this->assertEquals(
            [
                'https://example.com/build-tools/info.json',
                'https://example.com/build-tools2/info.json',
                'https://example.com/build-tools3/info.json'
            ],
            $config['repositories']
        );

        $this->assertArrayHasKey('artifact', $config);
        $this->assertEquals('.phpcq/build', $config['artifact']);

        $this->assertArrayHasKey('tools', $config);
        $this->assertEquals(
            [
                'phpunit' => [
                    'version' => '^7.0',
                ],
                'custom-task-tool' => [
                    'version' => '^1.0',
                ],
                'local-tool' => [
                    'runner-plugin' => '.phpcq/plugins/boot-local-tool.php'
                ],
            ],
            $config['tools']
        );
    }

    public function testMergeConfiguration(): void
    {
        $loader = new ConfigLoader(__DIR__ . '/fixtures/phpcq-merge.yaml');
        $config = $loader->getConfig();

        $this->assertEquals(
            [
                'directories'       => ['src', 'tests'],
                'repositories'      => [],
                'artifact'          => '.phpcq/build',
                'tools'             => [
                    'author-validation' => ['version' => '^1.0'],
                    'phpcpd'            => ['version' => '^2.0'],
                ],
                'author-validation' => [
                    'directories' => ['src' => null, 'examples' => null
                    ]
                ],
                'phpcpd'            => [
                    'customflags' => '',
                    'directories' => [
                        'src'   => null,
                        'tests' => null,
                        'a'     => null,
                        'b'     => null,
                        'xyz'   => [
                            'excluded'    => [
                                '... a (string)',
                                '... b (string)'
                            ],
                            'customflags' => null
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testMissingPhpcqConfiguration(): void
    {
        $loader = new ConfigLoader(__DIR__ . '/fixtures/invalid-config.yaml');

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Phpcq section missing');

        $loader->getConfig();
    }
}