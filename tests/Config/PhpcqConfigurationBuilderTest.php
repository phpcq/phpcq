<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config;

use Phpcq\Runner\Config\PhpcqConfigurationBuilder;
use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function var_dump;

/**
 * @covers \Phpcq\Runner\Config\PhpcqConfigurationBuilder
 */
final class PhpcqConfigurationBuilderTest extends TestCase
{
    public function testInstantiation(): void
    {
        $builder = new PhpcqConfigurationBuilder();
        $this->assertInstanceOf(PhpcqConfigurationBuilder::class, $builder);
    }

    public function testValidateConfiguration(): void
    {
        $config = [
            'repositories' => [
                'https://example.org/repository.json',
            ],
            'directories' => [
                'foo',
                'bar',
            ],
            'artifact' => './phpcq/build',
            'auth' => [],
            'trusted-keys' => [],
        ];

        $builder = new PhpcqConfigurationBuilder();
        $configuration = $builder->processConfig($config);

        $this->assertEquals(
            [
                'repositories' => [
                    [
                        'type' => 'remote',
                        'url' => 'https://example.org/repository.json',
                    ],
                ],
                'directories' => [
                    'foo',
                    'bar',
                ],
                'artifact' => './phpcq/build',
                'auth' => [],
                'trusted-keys' => [],
                'composer' => [
                    'autodiscover' => true,
                ],
            ],
            $configuration,
        );
    }

    public function testMigratesLegacyRequirementsToNewFormat(): void
    {
        $config = [
            'plugins' => [
                'phpcs' => [
                    'requirements' => [
                        'phpcs' => [
                            'version' => '^3.0'
                        ]
                    ]
                ]
            ]
        ];

        $builder = new PhpcqConfigurationBuilder();
        $configuration = $builder->processConfig($config);

        $this->assertEquals(
            [
                'directories' => [],
                'repositories' => [],
                'artifact' => '.phpcq/build',
                'auth' => [],
                'trusted-keys' => [],
                'composer' => [
                    'autodiscover' => true,
                ],
                'plugins' => [
                    'phpcs' => [
                        'requirements' => [
                            'tools' => [
                                'phpcs' => [
                                    'version' => '^3.0',
                                    'signed' => true,
                                ]
                            ],
                        ],
                        'version' => '*',
                        'signed' => true,
                    ]
                ]

            ],
            $configuration,
        );
    }

    public function testFullRequirementsConfiguration(): void
    {
        $config = [
            'plugins' => [
                'phpcs' => [
                    'requirements' => [
                        'tools' => [
                            'phpcs' => [
                                'version' => '^3.0',
                                'signed' => false,
                            ],
                        ],
                        'composer' => [
                            'phpcq/coding-standard' => '^1.0'
                        ]
                    ]
                ]
            ]
        ];

        $builder = new PhpcqConfigurationBuilder();
        $configuration = $builder->processConfig($config);

        $this->assertEquals(
            [
                'directories' => [],
                'repositories' => [],
                'artifact' => '.phpcq/build',
                'auth' => [],
                'trusted-keys' => [],
                'composer' => [
                    'autodiscover' => true,
                ],
                'plugins' => [
                    'phpcs' => [
                        'requirements' => [
                            'tools' => [
                                'phpcs' => [
                                    'version' => '^3.0',
                                    'signed' => false,
                                ],
                            ],
                            'composer' => [
                                'phpcq/coding-standard' => '^1.0'
                            ],
                        ],
                        'version' => '*',
                        'signed' => true,
                    ]
                ]

            ],
            $configuration,
        );
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidConfigurationProvider(): iterable
    {
        yield 'scalar option' => [['artifact' => []], 'phpcq.artifact'];
        yield 'nested option' => [['composer' => ['autodiscover' => []]], 'phpcq.composer.autodiscover'];
        yield 'prototype option' => [['plugins' => ['a' => ['version' => 1]]], 'phpcq.plugins.a.version'];
        yield 'unexpected key' => [['unknown' => 1], 'phpcq.unknown'];
    }

    /** @param array<string, mixed> $config */
    #[DataProvider('invalidConfigurationProvider')]
    public function testReportsErrorPath(array $config, string $path): void
    {
        $this->expectException(ConfigurationValidationErrorException::class);
        $this->expectExceptionMessage('Configuration validation failed at path "' . $path . '"');

        (new PhpcqConfigurationBuilder())->processConfig($config);
    }
}
