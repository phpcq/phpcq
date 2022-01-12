<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Console\Definition;

use Phpcq\Runner\Console\Definition\ArgumentDefinition;
use Phpcq\Runner\Console\Definition\CommandDefinition;
use Phpcq\Runner\Console\Definition\OptionDefinition;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Console\Definition\CommandDefinitionTest */
final class CommandDefinitionTest extends TestCase
{
    public function testDefinition(): void
    {
        $optionA = new OptionDefinition('a', 'Desc', null, false, false, false, null, ' ');
        $optionB = new OptionDefinition('b', 'Desc', null, false, false, false, null, ' ');
        $argument = new ArgumentDefinition('arg', 'Desc', false, false, null);

        $definition = new CommandDefinition(
            'cmd',
            'Command description',
            [$argument],
            [$optionA, $optionB]
        );

        self::assertSame('cmd', $definition->getName());
        self::assertSame('Command description', $definition->getDescription());
        self::assertSame([$optionA, $optionB], $definition->getOptions());
        self::assertSame([$argument], $definition->getArguments());
    }
}
