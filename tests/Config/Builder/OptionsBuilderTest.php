<?php

declare(strict_types=1);

namespace Phpcq\Test\Config\Builder;

use Phpcq\Config\Builder\OptionsBuilder;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Config\Builder\OptionsBuilder */
final class OptionsBuilderTest extends TestCase
{
    use OptionBuilderTestTrait;

    public function testDefaultValue(): void
    {
        $builder = $this->createInstance();
        $this->assertSame($builder, $builder->withDefaultValue(['bar']));
        $this->assertEquals(['bar'], $builder->normalizeValue(null));
    }

    public function testNormalizesValue(): void
    {
        $builder = $this->createInstance();
        $this->assertSame($builder, $builder->withNormalizer(function () { return ['BAR']; }));
        $this->assertSame($builder, $builder->withNormalizer(function ($var) { return array_merge($var,  ['2']); }));
        $this->assertEquals(['BAR', '2'], $builder->normalizeValue(['bar']));
    }

    public function testValidatesValue(): void
    {
        $builder = $this->createInstance();
        $builder->describeBoolOption('bar', 'Bar option');
        $validated = 0;

        $this->assertSame($builder, $builder->withValidator(function () use (&$validated) { $validated++; }));
        $this->assertSame($builder, $builder->withValidator(function () use (&$validated) { $validated++; }));

        $builder->normalizeValue(['bar' => true]);
        $builder->validateValue(['bar' => true]);
        $this->assertEquals(2, $validated);
    }

    public function testInvalidValue(): void
    {
        $builder = $this->createInstance();
        $builder->describeBoolOption('bar', 'Bar option');

        $this->expectException(InvalidConfigurationException::class);
        $builder->validateValue(['foo' => 'Bar']);
    }

    protected function createInstance(): OptionsBuilder
    {
        return new OptionsBuilder('Option', 'Example option');
    }
}
