<?php

declare(strict_types=1);

namespace Phpcq\Test\Config;

use Phpcq\Config\PhpcqConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * @covers \Phpcq\Config\PhpcqConfiguration
 */
final class PhpcqConfigurationTest extends TestCase
{
    public function testCreateTreeBuilder(): void
    {
        $configuration = new PhpcqConfiguration();

        $this->assertInstanceOf(TreeBuilder::class, $configuration->getConfigTreeBuilder());
    }
}
