<?php

declare(strict_types=1);

namespace Phpcq\Test\Config\Builder;

use Phpcq\Config\Builder\EnumOptionBuilder;
use Phpcq\Config\Builder\StringOptionBuilder;
use Phpcq\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Config\Builder\EnumOptionBuilder
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EnumOptionBuilderTest extends TestCase
{
    use OptionBuilderTestTrait;

    public function testNormalizesValue(): void
    {
        $builder = $this->createInstance();
        $builder->ofStringValues('bar');
        $builder->selfValidate();

        $this->assertSame($builder, $builder->withNormalizer(function () {
            return 'BAR';
        }));
        $this->assertSame($builder, $builder->withNormalizer(function ($var) {
            return $var . ' 2';
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

        $builder->ofStringValues('bar');
        $builder->selfValidate();
        $builder->normalizeValue('bar');
        $builder->validateValue('bar');
        $this->assertEquals(2, $validated);
    }

    public function testStringEnum(): void
    {
        $builder = $this->createInstance();
        $builder->ofStringValues('foo', 'bar');

        $this->assertEquals('foo', $builder->normalizeValue('foo'));
        $this->assertEquals('bar', $builder->normalizeValue('bar'));
    }

    public function testDefaultValue(): void
    {
        $builder = $this->createInstance();
        $this->assertInstanceOf(StringOptionBuilder::class, $builder->ofStringValues('bar')->withDefaultValue('bar'));
        $this->assertEquals('bar', $builder->normalizeValue(null));
    }

    public function testStringEnumInvalidValue(): void
    {
        $builder = $this->createInstance();
        $builder->ofStringValues('foo', 'bar');

        $this->expectException(InvalidConfigurationException::class);
        $builder->validateValue('baz');
    }

    public function testIntEnum(): void
    {
        $builder = $this->createInstance();
        $builder->ofIntValues(1, 2);

        $this->assertEquals(1, $builder->normalizeValue(1));
        $this->assertEquals(2, $builder->normalizeValue(2));
    }

    public function testIntEnumInvalidValue(): void
    {
        $builder = $this->createInstance();
        $builder->ofIntValues(1, 2);

        $this->expectException(InvalidConfigurationException::class);
        $builder->validateValue('baz');
    }

    public function testFloatEnum(): void
    {
        $builder = $this->createInstance();
        $builder->ofFloatValues(1.0, 2.2);

        $this->assertEquals(1.0, $builder->normalizeValue(1.0));
        $this->assertEquals(2.2, $builder->normalizeValue(2.2));
    }

    public function testFloatEnumInvalidValue(): void
    {
        $builder = $this->createInstance();
        $builder->ofFloatValues(1.0, 2.2);

        $this->expectException(InvalidConfigurationException::class);
        $builder->validateValue(1);
    }

    public function preventMultipleValueDefinitionsProvider(): array
    {
        return [
            'prevent double string definitions' => [
                'methodA'    => 'ofStringValues',
                'argumentsA' => ['foo'],
                'methodB'    => 'ofStringValues',
                'argumentsB' => ['bar']
            ],
            'prevent double int definitions' => [
                'methodA'    => 'ofIntValues',
                'argumentsA' => [1],
                'methodB'    => 'ofIntValues',
                'argumentsB' => [2]
            ],
            'prevent double float definitions' => [
                'methodA'    => 'ofFloatValues',
                'argumentsA' => [1.0],
                'methodB'    => 'ofFloatValues',
                'argumentsB' => [2.0]
            ],
            'prevent string and int definitions' => [
                'methodA'    => 'ofStringValues',
                'argumentsA' => ['foo'],
                'methodB'    => 'ofIntValues',
                'argumentsB' => [1]
            ],
            'prevent int and string definitions' => [
                'methodA'    => 'ofIntValues',
                'argumentsA' => [2],
                'methodB'    => 'ofStringValues',
                'argumentsB' => ['foo']
            ],
            'prevent float and int definitions' => [
                'methodA'    => 'ofFloatValues',
                'argumentsA' => [2.3],
                'methodB'    => 'ofIntValues',
                'argumentsB' => [1]
            ],
            'prevent int and float definitions' => [
                'methodA'    => 'ofIntValues',
                'argumentsA' => [2],
                'methodB'    => 'ofFloatValues',
                'argumentsB' => [2.0]
            ],
            'prevent string and float definitions' => [
                'methodA'    => 'ofStringValues',
                'argumentsA' => ['foo'],
                'methodB'    => 'ofFloatValues',
                'argumentsB' => [1.2]
            ],
            'prevent float and string definitions' => [
                'methodA'    => 'ofFloatValues',
                'argumentsA' => [2.0],
                'methodB'    => 'ofStringValues',
                'argumentsB' => ['foo']
            ],
        ];
    }

    /** @dataProvider preventMultipleValueDefinitionsProvider */
    public function testPreventsMultipleValueDefinitions(
        string $methodA,
        array $argumentsA,
        string $methodB,
        array $argumentsB
    ): void {
        $this->expectException(RuntimeException::class);

        $builder = $this->createInstance();
        $builder->$methodA(... $argumentsA);
        $builder->$methodB(... $argumentsB);
    }

    protected function createInstance(array $validators = []): EnumOptionBuilder
    {
        return new EnumOptionBuilder('Option', 'Example option', $validators);
    }
}
