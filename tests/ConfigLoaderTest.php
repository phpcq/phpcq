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
    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
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

        $this->assertArrayHasKey('plugins', $config);
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
                'local-tool'        => [
                    'runner-plugin' => '.phpcq/plugins/boot-local-tool.php',
                    'signed'        => true,
                    'version'       => '*',
                ],
            ],
            $config['plugins']
        );

        $this->assertArrayHasKey('chains', $config);
        $this->assertEquals(
            [
                'default' => [
                    'phpcpd',
                    'author-validation',
                    'autoload-validation',
                    'branch-alias-validation',
                    'composer-validate',
                    'pdepend',
                    'phpcs',
                    'phplint',
                    'phploc',
                    'phpmd',
                    'phpspec',
                    'travis-configuration-check',
                ],
                'tests' => [
                    'phpunit'
                ]
            ],
            $config['chains']
        );

        $this->assertArrayHasKey('tasks', $config);
        $this->assertEquals(
            [
                'phpunit' => [
                    'directories' => [
                        'foo'
                    ],
                    'config' => [
                        'customflags' => null,
                    ],
                ],
                'phpcpd' => [
                    'directories' => [
                        'a',
                        'b',
                        'xyz',
                    ],
                    'config' => [
                        'customflags' => null,
                    ],
                ],
                'author-validation' => [
                    'directories' => [
                        'src'
                    ],
                    'config' => [
                        'composer' => false,
                        'bower' => false,
                        'packages' => false,
                        'php-files' => true,
                    ],
                ],
                'autoload-validation' => [
                    'config' => [
                        'excluded' => false,
                        'customflags' => null,
                    ],
                ],
                'branch-alias-validation' => null,
                'composer-validate' => null,
                'pdepend' => [
                    'config' => [
                        'excluded' => null,
                        'src' => null,
                        'output' => null,
                    ],
                ],
                'phpcs' => [
                    'config' => [
                        'excluded' => null,
                        'src' => null,
                        'standard' => null,
                        'customflags' => null,
                    ],
                ],
                'phplint' => [
                    'config' => [
                        'src' => null,
                    ],
                ],
                'phploc' => [
                    'config' => [
                        'excluded' => null,
                        'src' => null,
                        'output' => null,
                    ],
                ],
                'phpmd' => [
                    'config' => [
                        'excluded' => null,
                        'src' => null,
                        'format' => null,
                        'ruleset' => null,
                        'customflags' => null,
                    ],
                ],
                'phpspec' => [
                    'config' => [
                        'format' => null,
                    ],
                ],
                'travis-configuration-check' => [
                    'config' => [
                        'customflags' => null,
                    ],
                ],
            ],
            $config['tasks']
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

    public function testMissingPhpcqConfiguration(): void
    {
        $loader = new ConfigLoader(__DIR__ . '/fixtures/invalid-config.yaml');

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Phpcq section missing');

        $loader->getConfig();
    }
}
