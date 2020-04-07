<?php

declare(strict_types=1);

namespace Phpcq\Test\Repository;

use Phpcq\Exception\RuntimeException;
use Phpcq\Repository\InstalledBootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Repository\InstalledBootstrap
 */
class InstalledBootstrapTest extends TestCase
{
    public function testGetters(): void
    {
        $instance = new InstalledBootstrap('1.0.0', __DIR__ . '/../fixtures/plugins/demo-plugin-bootstrap.php');
        $this->assertSame('1.0.0', $instance->getPluginVersion());
        $this->assertSame(__DIR__ . '/../fixtures/plugins/demo-plugin-bootstrap.php', $instance->getFilePath());
        $this->assertSame(file_get_contents(__DIR__ . '/../fixtures/plugins/demo-plugin-bootstrap.php'), $instance->getCode());
    }

    public function testThrowsForInvalidVersion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid version string: 11.0.0');
        new InstalledBootstrap('11.0.0', __DIR__ . '/../fixtures/plugins/demo-plugin-bootstrap.php');
    }

    public function testThrowsForInvalidPath(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File not found: /does/not/exist');
        new InstalledBootstrap('1.0.0', '/does/not/exist');
    }
}