<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Exception;

use Exception;
use Phpcq\Runner\Exception\ToolVersionNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Exception\ToolVersionNotFoundException
 */
final class ToolNotFoundExceptionTest extends TestCase
{
    public function testInitializesCorrectly(): void
    {
        $previous  = new Exception();
        $exception = new ToolVersionNotFoundException('supertool', '^1.0.0.0', 0, $previous);
        $this->assertSame('Tool not found: supertool:^1.0.0.0', $exception->getMessage());
        $this->assertSame('supertool', $exception->getToolName());
        $this->assertSame('^1.0.0.0', $exception->getVersionConstraint());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
