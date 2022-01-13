<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config\Builder;

use Phpcq\Runner\Config\Builder\AbstractOptionBuilder;
use Phpcq\Runner\Config\Builder\ConfigOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;

trait OptionBuilderTestTrait
{
    public function testInstantiation(): void
    {
        $builder = $this->createInstance();
        $this->assertInstanceOf(OptionBuilderInterface::class, $builder);
        $this->assertInstanceOf(AbstractOptionBuilder::class, $builder);
    }

    public function testIsRequired(): void
    {
        $builder = $this->createInstance();

        $this->assertSame($builder, $builder->isRequired());
        $this->expectException(InvalidConfigurationException::class);
        $builder->validateValue(null);
    }

    abstract public function testDefaultValue(): void;

    abstract public function testNormalizesValue(): void;

    abstract public function testValidatesValue(): void;

    abstract protected function createInstance(): ConfigOptionBuilderInterface;
}
