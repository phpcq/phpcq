<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Console\Definition\OptionValue;

use Phpcq\Runner\Console\Definition\OptionValue\OptionParamsDefinition;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Console\Definition\OptionValue\OptionParamsDefinition */
final class OptionParamsDefinitionTest extends TestCase
{
    public function testDefinition(): void
    {
        $definition = new OptionParamsDefinition(false, ['foo' => true, 'bar' => false]);

        self::assertSame(false, $definition->isRequired());
        self::assertSame(['foo' => true, 'bar' => false], $definition->getParams());
    }
}
