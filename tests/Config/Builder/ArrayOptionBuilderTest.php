<?php

declare(strict_types=1);

namespace Phpcq\Test\Config\Builder;

use Phpcq\Config\Builder\ArrayOptionBuilder;
use Phpcq\PluginApi\Version10\Configuration\Builder\NodeBuilderInterface;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Config\Builder\ArrayOptionBuilder */
final class ArrayOptionBuilderTest extends TestCase
{
    use OptionBuilderTestTrait;

    public function testDefaultValue(): void
    {
        $builder = $this->createInstance();
        $this->assertSame($builder, $builder->withDefaultValue(['bar']));
        $this->assertEquals(['bar'], $builder->processConfig(null));
    }

    public function testNormalizesValue(): void
    {
        $builder = $this->createInstance();
        $this->assertSame($builder, $builder->withNormalizer(function () { return ['BAR']; }));
        $this->assertSame($builder, $builder->withNormalizer(function ($var) { return array_merge($var,  ['2']); }));
        $this->assertEquals(['BAR', '2'], $builder->processConfig(['bar']));
    }

    public function testValidatesValue(): void
    {
        $builder = $this->createInstance();
        $validated = 0;

        $this->assertSame($builder, $builder->withValidator(function () use (&$validated) { $validated++; }));
        $this->assertSame($builder, $builder->withValidator(function () use (&$validated) { $validated++; }));

        $builder->processConfig(['bar']);
        $this->assertEquals(2, $validated);
    }

    protected function createInstance(?NodeBuilderInterface $parent = null): ArrayOptionBuilder
    {
        $parent = $parent ?: $this->getMockForAbstractClass(NodeBuilderInterface::class);

        return new ArrayOptionBuilder($parent, 'Option', 'Example option');
    }
}