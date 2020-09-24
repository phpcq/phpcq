<?php

declare(strict_types=1);

namespace Phpcq\Test\Command;

use Phpcq\Command\AbstractCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Command\AbstractCommand
 */
class AbstractCommandTest extends TestCase
{
    public function testConfigureHonorsConfigArgument()
    {
        $command    = $this->getMockForAbstractClass(AbstractCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('config'));
        $option = $definition->getOption('config');
        $this->assertTrue($option->isValueRequired());
        $this->assertSame($option->getDefault(), null);

        $this->assertTrue($definition->hasOption('home-dir'));
        $option = $definition->getOption('home-dir');
        $this->assertTrue($option->isValueRequired());
        $this->assertSame($option->getDefault(), getcwd() . '/.phpcq');
    }
}
