<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Console\Definition\OptionValue;

use Phpcq\Runner\Console\Definition\OptionValue\SimpleOptionValueDefinition;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Console\Definition\OptionValue\SimpleOptionValueDefinition */
final class SimpleOptionValueDefinitionTest extends TestCase
{
    public function testDefinition(): void
    {
        $definition = new SimpleOptionValueDefinition(false, 'foo', 'param');

        self::assertSame(false, $definition->isRequired());
        self::assertSame('foo', $definition->getDefaultValue());
        self::assertSame('param', $definition->getValueName());
    }
}
