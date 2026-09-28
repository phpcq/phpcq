<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config\Builder;

use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use Phpcq\Runner\Config\Builder\ArrayOptionBuilder;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Config\Builder\ArrayOptionBuilder */
final class ArrayOptionBuilderTest extends TestCase
{
    use OptionBuilderTestTrait;

    public function testDefaultValue(): void
    {
        $builder = $this->createInstance();
        $this->assertSame($builder, $builder->withDefaultValue(['foo' => 'bar']));
        $this->assertSame(['foo' => 'bar'], $builder->normalizeValue(null));
    }

    public function testNormalizesValue(): void
    {
        $builder = $this->createInstance();
        $this->assertSame($builder, $builder->withNormalizer(fn () => ['normalized' => true]));
        $this->assertSame(['normalized' => true], $builder->normalizeValue(['raw' => true]));
    }

    public function testValidatesValue(): void
    {
        $builder   = $this->createInstance();
        $validated = 0;

        $this->assertSame($builder, $builder->withValidator(function () use (&$validated) {
            $validated++;
        }));

        $builder->validateValue(['any' => ['nested' => 'value']]);
        $this->assertSame(1, $validated);
    }

    public function testKeepsUndescribedContent(): void
    {
        $value = ['any' => ['nested' => 'value'], 'list' => [1, 2]];

        $this->assertSame($value, $this->createInstance()->normalizeValue($value));
    }

    public function testInvalidValue(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->createInstance()->validateValue('string');
    }

    protected function createInstance(): ArrayOptionBuilder
    {
        return new ArrayOptionBuilder('option', 'Option configuration');
    }
}
