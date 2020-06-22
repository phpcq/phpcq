<?php

declare(strict_types=1);

namespace Phpcq\Test\Config\Builder;

use Phpcq\Config\Builder\AbstractOptionBuilder;
use Phpcq\Config\Builder\ConfigOptionBuilderInterface;
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

    abstract protected function createInstance(array $validators = []): ConfigOptionBuilderInterface;
}
