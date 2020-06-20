<?php

declare(strict_types=1);

namespace Phpcq\Test\Config\Builder;

use Phpcq\Config\Builder\ArrayOptionBuilder;
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
        $builder->describeBoolOption('bar', 'Bar option');
        $validated = 0;

        $this->assertSame($builder, $builder->withValidator(function () use (&$validated) { $validated++; }));
        $this->assertSame($builder, $builder->withValidator(function () use (&$validated) { $validated++; }));

        $builder->processConfig(['bar' => true]);
        $builder->validateValue(['bar' => true]);
        $this->assertEquals(2, $validated);
    }

    protected function createInstance(): ArrayOptionBuilder
    {
        return new ArrayOptionBuilder('Option', 'Example option');
    }
}
