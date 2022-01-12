<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Console\Definition;

use Phpcq\Runner\Console\Definition\OptionDefinition;
use Phpcq\Runner\Console\Definition\OptionValue\OptionValueDefinition;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Console\Definition\OptionDefinition */
final class OptionDefinitionTest extends TestCase
{
    public function testDefinition(): void
    {
        $optionValue = $this->getMockForAbstractClass(OptionValueDefinition::class, [], '', false);
        $definition = new OptionDefinition(
            'foo',
            'Full description',
            'f',
            true,
            false,
            false,
            $optionValue,
            '='
        );

        self::assertSame('foo', $definition->getName());
        self::assertSame('Full description', $definition->getDescription());
        self::assertSame('f', $definition->getShortcut());
        self::assertSame(true, $definition->isRequired());
        self::assertSame(false, $definition->isArray());
        self::assertSame(false, $definition->isOnlyShortcut());
        self::assertSame($optionValue, $definition->getOptionValue());
        self::assertSame('=', $definition->getValueSeparator());
    }

    public function testShortcutOnly(): void
    {
        $optionValue = $this->getMockForAbstractClass(OptionValueDefinition::class, [], '', false);
        $definition = new OptionDefinition(
            'foo',
            'Full description',
            null,
            true,
            false,
            true,
            $optionValue,
            '='
        );

        self::assertSame('foo', $definition->getName());
        self::assertSame('Full description', $definition->getDescription());
        self::assertSame('foo', $definition->getShortcut());
        self::assertSame(true, $definition->isRequired());
        self::assertSame(false, $definition->isArray());
        self::assertSame(true, $definition->isOnlyShortcut());
        self::assertSame($optionValue, $definition->getOptionValue());
        self::assertSame('=', $definition->getValueSeparator());
    }
}
