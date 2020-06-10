<?php

declare(strict_types=1);

namespace Phpcq\Test\Repository;

use Phpcq\Exception\RuntimeException;
use Phpcq\Repository\InlineBootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Repository\InlineBootstrap
 */
class InlineBootstrapTest extends TestCase
{
    public function testGetters(): void
    {
        $instance = new InlineBootstrap('1.0.0', 'code', null);
        $this->assertSame('1.0.0', $instance->getPluginVersion());
        $this->assertSame('code', $instance->getCode());
    }

    public function testThrowsForInvalidVersion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid version string: 11.0.0');
        new InlineBootstrap('11.0.0', 'code', null);
    }
}
