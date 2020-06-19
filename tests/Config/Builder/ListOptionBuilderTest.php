<?php

declare(strict_types=1);

namespace Phpcq\Test\Config\Builder;

use Phpcq\Config\Builder\ListOptionBuilder;
use Phpcq\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Configuration\Builder\NodeBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Config\Builder\ListOptionBuilder */
class ListOptionBuilderTest extends TestCase
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
        $validated = 0;

        $this->assertSame($builder, $builder->withValidator(function () use (&$validated) { $validated++; }));
        $this->assertSame($builder, $builder->withValidator(function () use (&$validated) { $validated++; }));

        $builder->processConfig(['bar']);
        $this->assertEquals(2, $validated);
    }

    public function testStringItems(): void
    {
        $builder = $this->createInstance();
        $builder->ofStringItems();

        $this->assertEquals(['foo', 'bar'], $builder->processConfig(['foo', 'bar']));
    }

    public function testStringItemsInvalidValue(): void
    {
        $builder = $this->createInstance();
        $builder->ofStringItems();

        $this->expectException(InvalidConfigurationException::class);
        $builder->processConfig([1.0, 1]);
    }

    public function testIntItems(): void
    {
        $builder = $this->createInstance();
        $builder->ofIntItems();

        $this->assertEquals([1, 2], $builder->processConfig([1, 2]));
    }

    public function testIntItemsInvalidValue(): void
    {
        $builder = $this->createInstance();
        $builder->ofIntItems();

        $this->expectException(InvalidConfigurationException::class);
        $builder->processConfig(['foo']);
    }

    public function testFloatItems(): void
    {
        $builder = $this->createInstance();
        $builder->ofFloatItems();

        $this->assertEquals([1.0, 2.2], $builder->processConfig([1.0, 2.2]));
    }

    public function testFloatItemsInvalidValue(): void
    {
        $builder = $this->createInstance();
        $builder->ofFloatItems();

        $this->expectException(InvalidConfigurationException::class);
        $builder->processConfig([1]);
    }

    public function preventMultipleValueDefinitionsProvider(): array
    {
        return [
            'prevent double string definitions' => [
                'methodA'    => 'ofStringItems',
                'methodB'    => 'ofStringItems',
            ],
            'prevent double int definitions' => [
                'methodA'    => 'ofIntItems',
                'methodB'    => 'ofIntItems',
            ],
            'prevent double float definitions' => [
                'methodA'    => 'ofFloatItems',
                'methodB'    => 'ofFloatItems',
            ],
            'prevent string and int definitions' => [
                'methodA'    => 'ofStringItems',
                'methodB'    => 'ofIntItems',
            ],
            'prevent int and string definitions' => [
                'methodA'    => 'ofIntItems',
                'methodB'    => 'ofStringItems',
            ],
            'prevent float and int definitions' => [
                'methodA'    => 'ofFloatItems',
                'methodB'    => 'ofIntItems',
            ],
            'prevent int and float definitions' => [
                'methodA'    => 'ofIntItems',
                'methodB'    => 'ofFloatItems',
            ],
            'prevent string and float definitions' => [
                'methodA'    => 'ofStringItems',
                'methodB'    => 'ofFloatItems',
            ],
            'prevent float and string definitions' => [
                'methodA'    => 'ofFloatItems',
                'methodB'    => 'ofStringItems',
            ],
        ];
    }

    /** @dataProvider preventMultipleValueDefinitionsProvider */
    public function testPreventsMultipleValueDefinitions(string $methodA, string $methodB): void
    {
        $this->expectException(RuntimeException::class);

        $builder = $this->createInstance();
        $builder->$methodA();
        $builder->$methodB();
    }

    protected function createInstance(?NodeBuilderInterface $parent = null, array $validators = []): ListOptionBuilder
    {
        $parent = $parent ?: $this->getMockForAbstractClass(NodeBuilderInterface::class);

        return new ListOptionBuilder($parent, 'Option', 'Example option', $validators);
    }
}