<?php

declare(strict_types=1);

namespace Phpcq\Test\Config;

use Phpcq\Config\PhpcqConfigurationBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Config\PhpcqConfigurationBuilder
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
                'https://example.org/repository.json'
            ],
            'directories' => [
                'foo',
                'bar'
            ],
            'artifact' => './phpcq/build',
            'chains' => [],
            'trusted-keys' => [],
        ];

        $builder = new PhpcqConfigurationBuilder();
        $configuration = $builder->processConfig($config);

        $this->assertEquals(
            [
                'repositories' => [
                    [
                        'type' => 'remote',
                        'url' => 'https://example.org/repository.json'
                    ]
                ],
                'directories' => [
                    'foo',
                    'bar'
                ],
                'artifact' => './phpcq/build',
                'chains' => [],
                'trusted-keys' => [],
            ],
            $configuration->getValue()
        );
    }

}
