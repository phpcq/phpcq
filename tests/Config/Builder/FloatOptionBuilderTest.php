<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config\Builder;

use Phpcq\Runner\Config\Builder\FloatOptionBuilder;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Config\Builder\FloatOptionBuilder */
final class FloatOptionBuilderTest extends TestCase
{
    use OptionBuilderTestTrait;

    public function testDefaultValue(): void
    {
        $builder = $this->createInstance();
        $this->assertSame($builder, $builder->withDefaultValue(2.5));
        $this->assertEquals(2.5, $builder->normalizeValue(null));
    }

    public function testNormalizesValue(): void
    {
        $builder = $this->createInstance();
        $this->assertSame($builder, $builder->withNormalizer(function () {
            return 3.0;
        }));
        $this->assertEquals(3.0, $builder->normalizeValue(7));
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

        $builder->validateValue(3.0);
        $this->assertEquals(2, $validated);
    }

    public function testInvalidValue(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $builder = $this->createInstance();
        $builder->validateValue(3);
    }

    protected function createInstance(array $validators = []): FloatOptionBuilder
    {
        return new FloatOptionBuilder('option', 'Option configuration', $validators);
    }
}
