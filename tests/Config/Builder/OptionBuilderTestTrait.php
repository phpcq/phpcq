<?php

declare(strict_types=1);

namespace Phpcq\Test\Config\Builder;

use Phpcq\Config\Builder\AbstractOptionBuilder;
use Phpcq\Config\Builder\OptionBuilder;
use Phpcq\PluginApi\Version10\Configuration\Builder\NodeBuilderInterface;
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

    public function testReturnsParent(): void
    {
        $parent  = $this->getMockForAbstractClass(NodeBuilderInterface::class);
        $builder = $this->createInstance($parent);

        $this->assertSame($parent, $builder->end());
    }

    public function testIsRequired(): void
    {
        $builder = $this->createInstance();

        $this->assertSame($builder, $builder->isRequired());
        $this->expectException(InvalidConfigurationException::class);
        $builder->processConfig(null);
    }

    public function testDefaultValue(): void
    {
        $builder = $this->createInstance();
        $this->assertSame($builder, $builder->withDefaultValue('bar'));
        $this->assertEquals('bar', $builder->processConfig(null));
    }

    public function testNormalizesValue(): void
    {
        $builder = $this->createInstance();
        $this->assertSame($builder, $builder->withNormalizer(function () { return 'BAR'; }));
        $this->assertSame($builder, $builder->withNormalizer(function ($var) { return $var . ' 2'; }));
        $this->assertEquals('BAR 2', $builder->processConfig('bar'));
    }

    public function testValidatesValue(): void
    {
        $builder = $this->createInstance();
        $validated = 0;

        $this->assertSame($builder, $builder->withValidator(function () use (&$validated) { $validated++; }));
        $this->assertSame($builder, $builder->withValidator(function () use (&$validated) { $validated++; }));

        $builder->processConfig('bar');
        $builder->validateValue('bar');
        $this->assertEquals(2, $validated);
    }

    abstract protected function createInstance(?NodeBuilderInterface $parent = null, array $validators = []);
}
