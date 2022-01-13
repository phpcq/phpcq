<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Console\Builder;

use Phpcq\Runner\Console\Definition\Builder\ConsoleArgumentBuilder;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Console\Definition\Builder\ConsoleArgumentBuilder */
final class ConsoleArgumentBuilderTest extends TestCase
{
    public function testDefaults(): void
    {
        $builder = new ConsoleArgumentBuilder('foo', 'Description');
        $definition = $builder->build();

        self::assertSame('foo', $definition->getName());
        self::assertSame('Description', $definition->getDescription());
        self::assertFalse($definition->isRequired());
        self::assertFalse($definition->isArray());
        self::assertNull($definition->getDefaultValue());
    }

    public function testRequired(): void
    {
        $builder = new ConsoleArgumentBuilder('foo', 'Description');
        $builder->isRequired();

        $definition = $builder->build();

        self::assertSame('foo', $definition->getName());
        self::assertSame('Description', $definition->getDescription());
        self::assertTrue($definition->isRequired());
        self::assertFalse($definition->isArray());
        self::assertNull($definition->getDefaultValue());
    }

    public function testMultiple(): void
    {
        $builder = new ConsoleArgumentBuilder('foo', 'Description');
        $builder->isArray();

        $definition = $builder->build();

        self::assertSame('foo', $definition->getName());
        self::assertSame('Description', $definition->getDescription());
        self::assertFalse($definition->isRequired());
        self::assertTrue($definition->isArray());
        self::assertNull($definition->getDefaultValue());
    }

    public function testDefaultValue(): void
    {
        $builder = new ConsoleArgumentBuilder('foo', 'Description');
        $builder->withDefaultValue('bar');

        $definition = $builder->build();

        self::assertSame('foo', $definition->getName());
        self::assertSame('Description', $definition->getDescription());
        self::assertFalse($definition->isRequired());
        self::assertFalse($definition->isArray());
        self::assertSame('bar', $definition->getDefaultValue());
    }
}
