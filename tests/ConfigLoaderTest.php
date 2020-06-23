<?php

declare(strict_types=1);

namespace Phpcq\Test;

use Phpcq\ConfigLoader;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\ConfigLoader
 */
final class ConfigLoaderTest extends TestCase
{
    public function testFullFeaturedConfigFile(): void
    {
        $loader = new ConfigLoader(__DIR__ . '/fixtures/phpcq-demo.yaml');
        $config = $loader->getConfig()->asArray();

        $this->assertArrayHasKey('directories', $config);
        $this->assertEquals(['src', 'tests'], $config['directories']);

        $this->assertArrayHasKey('repositories', $config);
        $this->assertEquals(
            [
                [
                    'type' => 'remote',
                    'url'  => 'https://example.com/build-tools/info.json',
                ],
                [
                    'type' => 'remote',
                    'url'  => 'https://example.com/build-tools2/info.json',
                ],
                [
                    'type' => 'remote',
                    'url'  => 'https://example.com/build-tools3/info.json'
                ],
            ],
            $config['repositories']
        );

        $this->assertArrayHasKey('artifact', $config);
        $this->assertEquals('.phpcq/build', $config['artifact']);

        $this->assertArrayHasKey('tools', $config);
        $this->assertEquals(
            [
                'phpunit'          => [
                    'version' => '^7.0',
                    'signed'  => true,
                ],
                'custom-task-tool' => [
                    'version' => '^1.0',
                    'signed'  => true,
                ],
                'local-tool'       => [
                    'runner-plugin' => '.phpcq/plugins/boot-local-tool.php',
                    'signed'        => true,
                ],
            ],
            $config['tools']
        );

        $this->assertEquals(
            [
                'default' => [
                    'phpcpd' => null,
                    'author-validation' => null,
                    'autoload-validation' => null,
                    'branch-alias-validation' => null,
                    'composer-validate' => null,
                    'pdepend' => null,
                    'phpcs' => null,
                    'phplint' => null,
                    'phploc' => null,
                    'phpmd' => null,
                    'phpspec' => null,
                    'travis-configuration-check' => null,
                ],
                'tests' => [
                    'phpunit' => [
                        'directories' => [
                            'foo' => null
                        ]
                    ]
                ]
            ],
            $config['chains']
        );

        $this->assertEquals(
            [
                '4AA394086372C20A',
                '8A03EA3B385DBAA1',
                'D2CCAC42F6295E7D'
            ],
            $config['trusted-keys']
        );
    }

    public function testMergeConfiguration(): void
    {
        $loader = new ConfigLoader(__DIR__ . '/fixtures/phpcq-merge.yaml');
        $config = $loader->getConfig()->asArray();

        $this->assertEquals(
            [
                'directories'       => ['src', 'tests'],
                'repositories'      => [],
                'artifact'          => '.phpcq/build',
                'tools'             => [
                    'author-validation' => ['version' => '^1.0', 'signed' => true],
                    'phpcpd'            => ['version' => '^2.0', 'signed' => true],
                ],
                'trusted-keys' => [],
                'chains' => ['default' => [
                    'author-validation' => null,
                    'phpcpd'            => null,
                ]],
                'tool-config' => [
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
                ]
            ],
            $config
        );
    }

    public function testMissingPhpcqConfiguration(): void
    {
        $loader = new ConfigLoader(__DIR__ . '/fixtures/invalid-config.yaml');

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Phpcq section missing');

        $loader->getConfig();
    }
}
