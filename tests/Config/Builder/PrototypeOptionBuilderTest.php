<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config\Builder;

use Phpcq\Runner\Config\Builder\PrototypeOptionBuilder;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

use function array_merge;

/** @covers \Phpcq\Runner\Config\Builder\PrototypeOptionBuilder */
final class PrototypeOptionBuilderTest extends TestCase
{
    use OptionBuilderTestTrait;

    public function testDefaultValue(): void
    {
        $builder = $this->createInstance();
        $builder->ofStringValue();
        $this->assertSame($builder, $builder->withDefaultValue(['bar']));
        $this->assertEquals(['bar'], $builder->normalizeValue(null));
    }

    public function testNormalizesValue(): void
    {
        $builder = $this->createInstance();
        $builder->ofStringValue();
        $this->assertSame($builder, $builder->withNormalizer(function () {
            return ['BAR'];
        }));
        $this->assertSame($builder, $builder->withNormalizer(function ($var) {
            return array_merge($var, ['2']);
        }));
        $this->assertEquals(['BAR', '2'], $builder->normalizeValue(['bar']));
    }

    public function testValidatesValue(): void
    {
        $builder = $this->createInstance();
        $builder->ofStringValue();
        $validated = 0;

        $this->assertSame($builder, $builder->withValidator(function () use (&$validated) {
            $validated++;
        }));
        $this->assertSame($builder, $builder->withValidator(function () use (&$validated) {
            $validated++;
        }));

        $builder->validateValue(['bar' => 'baz', 'foo' => 'example']);
        $this->assertEquals(2, $validated);
    }

    public function testInvalidValue(): void
    {
        $builder = $this->createInstance();
        $builder->ofStringValue();

        $this->expectException(InvalidConfigurationException::class);
        $builder->validateValue('bar');
    }

    protected function createInstance(array $validators = []): PrototypeOptionBuilder
    {
        return new PrototypeOptionBuilder('option', 'Option configuration', $validators);
    }
}
