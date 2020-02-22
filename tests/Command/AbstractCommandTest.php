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

        $this->assertTrue($definition->hasArgument('config'));
        $argument = $definition->getArgument('config');
        $this->assertFalse($argument->isRequired());
        $this->assertSame($argument->getDefault(), getcwd() . '/.phpcq.yaml');
    }
}
