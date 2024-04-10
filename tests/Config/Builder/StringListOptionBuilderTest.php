<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config\Builder;

use Phpcq\Runner\Config\Builder\StringListOptionBuilder;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Config\Builder\StringListOptionBuilder */
final class StringListOptionBuilderTest extends TestCase
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

        $this->assertSame($builder, $builder->withNormalizer(function () {
            return 'BAR';
        }));
        $this->assertEquals(['BAR'], $builder->normalizeValue(['bar']));
    }

    public function testValidatesValue(): void
    {
        $builder = $this->createInstance();

        $validated = 0;

        $this->assertSame($builder, $builder->withValidator(function () use (&$validated) {
            $validated++;
        }));
        $this->assertSame($builder, $builder->withValidator(function () use (&$validated) {
            $validated++;
        }));

        $builder->normalizeValue(['bar']);
        $builder->validateValue(['bar']);
        $this->assertEquals(2, $validated);
    }

    public function testStringItems(): void
    {
        $builder = $this->createInstance();

        $this->assertEquals(['foo', 'bar'], $builder->normalizeValue(['foo', 'bar']));
    }

    public function testStringItemsInvalidValue(): void
    {
        $builder = $this->createInstance();

        $this->expectException(InvalidConfigurationException::class);
        $builder->normalizeValue([1.0, 1]);
        $builder->validateValue([1.0, 1]);
    }

    protected function createInstance(): StringListOptionBuilder
    {
        return new StringListOptionBuilder('Option', 'Example option');
    }
}
