<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config\Builder;

use Phpcq\Runner\Config\Builder\StringOptionBuilder;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Config\Builder\StringOptionBuilder */
final class StringOptionBuilderTest extends TestCase
{
    use OptionBuilderTestTrait;

    public function testDefaultValue(): void
    {
        $builder = $this->createInstance();
        $this->assertSame($builder, $builder->withDefaultValue('default-value'));
        $this->assertEquals('default-value', $builder->normalizeValue(null));
    }

    public function testNormalizesValue(): void
    {
        $builder = $this->createInstance();
        $this->assertSame($builder, $builder->withNormalizer(function () {
            return 'BAR';
        }));
        $this->assertSame($builder, $builder->withNormalizer(function ($value) {
            return $value . ' 2';
        }));

        $this->assertEquals('BAR 2', $builder->normalizeValue('bar'));
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

        $builder->validateValue('bar');
        $this->assertEquals(2, $validated);
    }

    public function testInvalidValue(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $builder = $this->createInstance();
        $builder->validateValue(5.0);
    }

    protected function createInstance(array $validators = []): StringOptionBuilder
    {
        return new StringOptionBuilder('option', 'Option configuration', $validators);
    }
}
