<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Console\Builder;

use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\Runner\Console\Definition\Builder\ConsoleOptionBuilder;
use Phpcq\Runner\Console\Definition\OptionValue\KeyValueMapOptionValueDefinition;
use Phpcq\Runner\Console\Definition\OptionValue\OptionParamsDefinition;
use Phpcq\Runner\Console\Definition\OptionValue\SimpleOptionValueDefinition;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Console\Definition\Builder\ConsoleOptionBuilder
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class ConsoleOptionBuilderTest extends TestCase
{
    public function testDefaults(): void
    {
        $builder = new ConsoleOptionBuilder('foo', 'Description');
        $definition = $builder->build('=');

        self::assertSame('foo', $definition->getName());
        self::assertSame('Description', $definition->getDescription());
        self::assertSame('=', $definition->getValueSeparator());
        self::assertFalse($definition->isRequired());
        self::assertFalse($definition->isArray());
        self::assertFalse($definition->isOnlyShortcut());
    }

    public function testRequired(): void
    {
        $builder = new ConsoleOptionBuilder('foo', 'Description');
        $builder->isRequired();

        $definition = $builder->build(':');

        self::assertSame('foo', $definition->getName());
        self::assertSame('Description', $definition->getDescription());
        self::assertSame(':', $definition->getValueSeparator());
        self::assertFalse($definition->isArray());
        self::assertFalse($definition->isOnlyShortcut());
        self::assertTrue($definition->isRequired());
    }

    public function testMultiple(): void
    {
        $builder = new ConsoleOptionBuilder('foo', 'Description');
        $builder->isArray();

        $definition = $builder->build('=');

        self::assertSame('foo', $definition->getName());
        self::assertSame('Description', $definition->getDescription());
        self::assertSame('=', $definition->getValueSeparator());
        self::assertTrue($definition->isArray());
        self::assertFalse($definition->isOnlyShortcut());
        self::assertFalse($definition->isRequired());
    }

    public function testShortcutOnly(): void
    {
        $builder = new ConsoleOptionBuilder('foo', 'Description');
        $builder->withShortcutOnly();

        $definition = $builder->build('=');

        self::assertSame('foo', $definition->getName());
        self::assertSame('Description', $definition->getDescription());
        self::assertSame('=', $definition->getValueSeparator());
        self::assertFalse($definition->isArray());
        self::assertTrue($definition->isOnlyShortcut());
        self::assertFalse($definition->isRequired());
    }

    public function testShortcut(): void
    {
        $builder = new ConsoleOptionBuilder('foo', 'Description');
        $builder->withShortcut('c');

        $definition = $builder->build('=');

        self::assertSame('foo', $definition->getName());
        self::assertSame('Description', $definition->getDescription());
        self::assertSame('=', $definition->getValueSeparator());
        self::assertSame('c', $definition->getShortcut());
    }

    public function testUnnamedOptionalValue(): void
    {
        $builder = new ConsoleOptionBuilder('foo', 'Description');
        $builder->withOptionalValue(null, true);

        $definition = $builder->build('=');
        $optionValue = $definition->getOptionValue();

        self::assertInstanceOf(SimpleOptionValueDefinition::class, $optionValue);
        self::assertSame(null, $optionValue->getValueName());
        self::assertSame(true, $optionValue->getDefaultValue());
        self::assertFalse($optionValue->isRequired());
    }

    public function testNamedOptionalValue(): void
    {
        $builder = new ConsoleOptionBuilder('foo', 'Description');
        $builder->withOptionalValue('bar', true);

        $definition = $builder->build('=');
        $optionValue = $definition->getOptionValue();

        self::assertInstanceOf(SimpleOptionValueDefinition::class, $optionValue);
        self::assertSame('bar', $optionValue->getValueName());
        self::assertSame(true, $optionValue->getDefaultValue());
        self::assertFalse($optionValue->isRequired());
    }

    public function testUnNamedRequiredValue(): void
    {
        $builder = new ConsoleOptionBuilder('foo', 'Description');
        $builder->withRequiredValue();

        $definition = $builder->build('=');
        $optionValue = $definition->getOptionValue();

        self::assertInstanceOf(SimpleOptionValueDefinition::class, $optionValue);
        self::assertSame(null, $optionValue->getValueName());
        self::assertTrue($optionValue->isRequired());
    }

    public function testNamedRequiredValue(): void
    {
        $builder = new ConsoleOptionBuilder('foo', 'Description');
        $builder->withRequiredValue('bar');

        $definition = $builder->build('=');
        $optionValue = $definition->getOptionValue();

        self::assertInstanceOf(SimpleOptionValueDefinition::class, $optionValue);
        self::assertSame('bar', $optionValue->getValueName());
        self::assertTrue($optionValue->isRequired());
    }

    public function testParams(): void
    {
        $builder = new ConsoleOptionBuilder('foo', 'Description');
        $builder->withRequiredValue('bar');
        $builder->withRequiredValue('baz');

        $definition = $builder->build('=');
        $optionValue = $definition->getOptionValue();

        self::assertInstanceOf(OptionParamsDefinition::class, $optionValue);
        self::assertEquals(['bar' => null, 'baz' => null], $optionValue->getParams());
    }

    public function testKeyValueMap(): void
    {
        $builder = new ConsoleOptionBuilder('foo', 'Description');
        $builder->withKeyValueMap(true, ':');

        $definition = $builder->build('=');
        $optionValue = $definition->getOptionValue();

        self::assertInstanceOf(KeyValueMapOptionValueDefinition::class, $optionValue);
        self::assertTrue($optionValue->isRequired());
        self::assertSame(true, $optionValue->getDefaultValue());
        self::assertSame(':', $optionValue->getValueSeparator());
    }

    public function testExclusiveKeyValueMapByWithRequiredValue(): void
    {
        $builder = new ConsoleOptionBuilder('foo', 'Description');
        $builder->withKeyValueMap(true, ':');

        self::expectException(RuntimeException::class);
        $builder->withRequiredValue();
    }

    public function testExclusiveKeyValueMapByWithOptionalValue(): void
    {
        $builder = new ConsoleOptionBuilder('foo', 'Description');
        $builder->withKeyValueMap(true, ':');

        self::expectException(RuntimeException::class);
        $builder->withOptionalValue();
    }

    public function testExclusiveOptionValue(): void
    {
        $builder = new ConsoleOptionBuilder('foo', 'Description');
        $builder->withOptionalValue();

        self::expectException(RuntimeException::class);
        $builder->withKeyValueMap(true, ':');
    }
}
