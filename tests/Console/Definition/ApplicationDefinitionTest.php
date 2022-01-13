<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Console\Definition;

use Phpcq\Runner\Console\Definition\ApplicationDefinition;
use Phpcq\Runner\Console\Definition\ArgumentDefinition;
use Phpcq\Runner\Console\Definition\CommandDefinition;
use Phpcq\Runner\Console\Definition\OptionDefinition;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Console\Definition\ApplicationDefinition */
final class ApplicationDefinitionTest extends TestCase
{
    public function testDefinition(): void
    {
        $optionA = new OptionDefinition('a', 'Desc', null, false, false, false, null, ' ');
        $optionB = new OptionDefinition('b', 'Desc', null, false, false, false, null, ' ');
        $argument = new ArgumentDefinition('arg', 'Desc', false, false, null);
        $commandA = new CommandDefinition('cmda', 'Desc', [], []);
        $commandB = new CommandDefinition('cmdb', 'Desc', [], []);
        $commandC = new CommandDefinition('cmdc', 'Desc', [], []);

        $definition = new ApplicationDefinition(
            'app:example',
            'Application description',
            [$optionA, $optionB],
            [$argument],
            [$commandA, $commandB, $commandC],
            '='
        );

        self::assertSame('app:example', $definition->getName());
        self::assertSame('Application description', $definition->getDescription());
        self::assertSame([$optionA, $optionB], $definition->getOptions());
        self::assertSame([$argument], $definition->getArguments());
        self::assertSame([$commandA, $commandB, $commandC], $definition->getCommands());

        self::assertSame($commandA, $definition->getCommand('cmda'));
        self::assertSame($commandB, $definition->getCommand('cmdb'));
        self::assertSame($commandC, $definition->getCommand('cmdc'));
    }
}
