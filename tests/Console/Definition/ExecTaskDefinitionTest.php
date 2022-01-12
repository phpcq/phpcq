<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Console\Definition;

use Phpcq\Runner\Console\Definition\ApplicationDefinition;
use Phpcq\Runner\Console\Definition\ExecTaskDefinition;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Console\Definition\ExecTaskDefinition */
final class ExecTaskDefinitionTest extends TestCase
{
    public function testDefinition(): void
    {
        $applicationA = new ApplicationDefinition('app:a', 'Application A', [], [], [], '=');
        $applicationB = new ApplicationDefinition('app:b', 'Application B', [], [], [], '=');
        $definition = new ExecTaskDefinition([$applicationA, $applicationB]);

        self::assertSame([$applicationA, $applicationB], $definition->getApplications());
        self::assertSame($applicationA, $definition->getApplication('app:a'));
        self::assertSame($applicationB, $definition->getApplication('app:b'));
    }

    public function testAlphabeticalApplicationSorting(): void
    {
        $applicationB = new ApplicationDefinition('app:b', 'Application B', [], [], [], '=');
        $applicationA = new ApplicationDefinition('app:a', 'Application A', [], [], [], '=');
        $definition = new ExecTaskDefinition([$applicationB, $applicationA]);

        self::assertEquals([$applicationA, $applicationB], $definition->getApplications());
    }
}
