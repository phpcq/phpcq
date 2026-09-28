<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config\Builder;

use Phpcq\Runner\Config\Builder\PrototypeOptionBuilder;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
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
        $this->assertSame($builder, $builder->withNormalizer(fn() => ['BAR']));
        $this->assertSame($builder, $builder->withNormalizer(fn($var) => array_merge($var, ['2'])));
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

    public function testErrorPathContainsPrototypeIndex(): void
    {
        $builder = $this->createInstance();
        $builder->ofOptionsValue()->describeBoolOption('flag', 'Flag');

        try {
            $builder->validateValue($builder->normalizeValue(['first' => ['flag' => 'yes']]));
            self::fail('Exception expected');
        } catch (ConfigurationValidationErrorException $exception) {
            self::assertSame(['option', 'first', 'flag'], $exception->getPath());
            self::assertSame(
                'Configuration validation failed at path "option.first.flag": Boolean expected, got string',
                $exception->getMessage()
            );
        }
    }
}
