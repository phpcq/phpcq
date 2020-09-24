<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config\Builder;

use Phpcq\Runner\Config\Builder\BoolOptionBuilder;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Config\Builder\BoolOptionBuilder */
final class BoolOptionBuilderTest extends TestCase
{
    use OptionBuilderTestTrait;

    public function testDefaultValue(): void
    {
        $builder = $this->createInstance();
        $this->assertSame($builder, $builder->withDefaultValue(true));
        $this->assertEquals(true, $builder->normalizeValue(null));
    }

    public function testNormalizesValue(): void
    {
        $builder = $this->createInstance();
        $this->assertSame($builder, $builder->withNormalizer(function () {
            return false;
        }));
        $this->assertEquals(false, $builder->normalizeValue('false'));
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

        $builder->normalizeValue(false);
        $builder->validateValue(false);
        $this->assertEquals(2, $validated);
    }

    public function testInvalidValue(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $builder = $this->createInstance();
        $builder->validateValue('false');
    }

    protected function createInstance(array $validators = []): BoolOptionBuilder
    {
        return new BoolOptionBuilder('option', 'Option configuration', $validators);
    }
}
