<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Console\Builder;

use Phpcq\Runner\Console\Definition\Builder\ConsoleApplicationBuilder;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Console\Definition\Builder\ConsoleArgumentBuilder */
final class ConsoleApplicationBuilderTest extends TestCase
{
    public function testDefaults(): void
    {
        $builder = new ConsoleApplicationBuilder('app:example', 'Description');

        $definition = $builder->build();

        self::assertSame('app:example', $definition->getName());
        self::assertSame('Description', $definition->getDescription());
        self::assertCount(0, $definition->getArguments());
        self::assertCount(0, $definition->getCommands());
        self::assertCount(0, $definition->getOptions());
        self::assertSame('=', $definition->getOptionValueSeparator());
    }

    public function testArguments(): void
    {
        $builder = new ConsoleApplicationBuilder('app:example', 'Description');
        $builder->describeArgument('a', 'Description');
        $builder->describeArgument('b', 'Description');

        $definition = $builder->build();

        self::assertCount(2, $definition->getArguments());
    }

    public function testCommands(): void
    {
        $builder = new ConsoleApplicationBuilder('app:example', 'Description');
        $builder->describeCommand('a', 'Description');
        $builder->describeCommand('b', 'Description');

        $definition = $builder->build();

        self::assertCount(2, $definition->getCommands());
    }

    public function testOptions(): void
    {
        $builder = new ConsoleApplicationBuilder('app:example', 'Description');
        $builder->describeOption('a', 'Description');
        $builder->describeOption('b', 'Description');

        $definition = $builder->build();

        self::assertCount(2, $definition->getOptions());
        self::assertSame('=', $definition->getOptions()[0]->getValueSeparator());
    }

    public function testCustomOptionValueSeparator(): void
    {
        $builder = new ConsoleApplicationBuilder('app:example', 'Description');
        $builder->withOptionValueSeparator('-');
        $builder
            ->describeOption('a', 'Description')
            ->withOptionalValue('foo');

        $definition = $builder->build();

        self::assertCount(1, $definition->getOptions());

        $option = $definition->getOptions()[0];
        self::assertSame('-', $option->getValueSeparator());
    }
}
