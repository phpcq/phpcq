<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Console\Definition;

use Phpcq\Runner\Console\Definition\ArgumentDefinition;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Console\Definition\ArgumentDefinition */
final class ArgumentDefinitionTest extends TestCase
{
    public function testDefinition(): void
    {
        $definition = new ArgumentDefinition(
            'arg',
            'Argument description',
            true,
            false,
            'foo'
        );

        self::assertSame('arg', $definition->getName());
        self::assertSame('Argument description', $definition->getDescription());
        self::assertSame(true, $definition->isRequired());
        self::assertSame(false, $definition->isArray());
        self::assertSame('foo', $definition->getDefaultValue());
    }
}
