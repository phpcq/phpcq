<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Console\Builder;

use Phpcq\Runner\Console\Definition\Builder\ConsoleCommandBuilder;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Console\Definition\Builder\ConsoleCommandBuilder */
final class ConsoleCommandBuilderTest extends TestCase
{
    public function testDefaults(): void
    {
        $builder = new ConsoleCommandBuilder('foo', 'Description');
        $definition = $builder->build('=');

        self::assertSame('foo', $definition->getName());
        self::assertSame('Description', $definition->getDescription());
        self::assertSame([], $definition->getArguments());
        self::assertSame([], $definition->getOptions());
    }

    public function testArguments(): void
    {
        $builder = new ConsoleCommandBuilder('foo', 'Description');
        $builder->describeArgument('one', 'One');
        $builder->describeArgument('two', 'Two');

        $definition = $builder->build('=');

        self::assertCount(2, $definition->getArguments());
    }

    public function testOptions(): void
    {
        $builder = new ConsoleCommandBuilder('foo', 'Description');
        $builder->describeOption('one', 'One');
        $builder->describeOption('two', 'Two');

        $definition = $builder->build('=');

        self::assertCount(2, $definition->getOptions());
    }

    public function testDefaultValueSeparator(): void
    {
        $builder = new ConsoleCommandBuilder('foo', 'Description');
        $builder->describeOption('one', 'One');

        $definition = $builder->build(':');

        self::assertCount(1, $definition->getOptions());

        $option = $definition->getOptions()[0];
        self::assertSame(':', $option->getValueSeparator());
    }
}
