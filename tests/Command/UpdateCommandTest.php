<?php

declare(strict_types=1);

namespace Phpcq\Test\Command;

use Phpcq\Command\UpdateCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Command\UpdateCommand
 */
final class UpdateCommandTest extends TestCase
{
    public function testConfigureHonorsCacheOption()
    {
        $command    = new UpdateCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('cache'));
        $option = $definition->getOption('cache');
        $this->assertTrue($option->isValueRequired());
        $this->assertSame($option->getDefault(), getenv('HOME') . '/.cache/phpcq');
    }
}
