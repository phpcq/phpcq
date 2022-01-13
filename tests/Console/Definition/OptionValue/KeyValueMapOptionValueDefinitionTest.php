<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Console\Definition\OptionValue;

use Phpcq\Runner\Console\Definition\OptionValue\KeyValueMapOptionValueDefinition;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Console\Definition\OptionValue\KeyValueMapOptionValueDefinition */
final class KeyValueMapOptionValueDefinitionTest extends TestCase
{
    public function testDefinition(): void
    {
        $definition = new KeyValueMapOptionValueDefinition(true, 'bar', ':');

        self::assertSame(true, $definition->isRequired());
        self::assertSame('bar', $definition->getDefaultValue());
        self::assertSame(':', $definition->getValueSeparator());
    }
}
