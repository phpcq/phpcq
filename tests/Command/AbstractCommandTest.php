<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Command;

use Phpcq\Runner\Command\AbstractCommand;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputDefinition;

/**
 * @covers \Phpcq\Runner\Command\AbstractCommand
 */
final class AbstractCommandTest extends TestCase
{
    public function testConfigureHonorsConfigArgument(): void
    {
        $command    = $this->createPartialMock(AbstractCommand::class, ['doExecute']);
        $definition = new InputDefinition();
        $command->setDefinition($definition);

        $reflectionMethod = new ReflectionMethod($command, 'configure');
        $reflectionMethod->invoke($command);

        $this->assertTrue($definition->hasOption('config'));
        $option = $definition->getOption('config');
        $this->assertTrue($option->isValueRequired());
        $this->assertSame(null, $option->getDefault());

        $this->assertTrue($definition->hasOption('home-dir'));
        $option = $definition->getOption('home-dir');
        $this->assertTrue($option->isValueRequired());
        $this->assertSame(getcwd() . '/.phpcq', $option->getDefault());
    }
}
