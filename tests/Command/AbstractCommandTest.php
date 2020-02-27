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
        $this->assertSame($option->getDefault(), getcwd() . '/.phpcq.yaml');

        $this->assertTrue($definition->hasOption('tools'));
        $option = $definition->getOption('tools');
        $this->assertTrue($option->isValueRequired());
        $this->assertSame($option->getDefault(), getcwd() . '/vendor/phpcq');

        $this->assertTrue($definition->hasOption('cache'));
        $option = $definition->getOption('cache');
        $this->assertTrue($option->isValueRequired());
        $this->assertSame($option->getDefault(), getenv('HOME') . '/.cache/phpcq');
    }
}
