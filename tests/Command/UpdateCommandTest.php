<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Command;

use Phpcq\Runner\Command\UpdateCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Command\UpdateCommand
 */
final class UpdateCommandTest extends TestCase
{
    public function testConfigureHonorsCacheOption(): void
    {
        $command    = new UpdateCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('cache'));
        $option = $definition->getOption('cache');
        $this->assertTrue($option->isValueRequired());
        $this->assertSame($option->getDefault(), getenv('HOME') . '/.cache/phpcq');
    }
}
