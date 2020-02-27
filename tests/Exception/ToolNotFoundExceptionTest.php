<?php

declare(strict_types=1);

namespace Config;

use Exception;
use Phpcq\Exception\ToolNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Exception\ToolNotFoundException
 */
final class ToolNotFoundExceptionTest extends TestCase
{
    public function testInitializesCorrectly()
    {
        $previous  = new Exception();
        $exception = new ToolNotFoundException('supertool', '^1.0.0.0', 0, $previous);
        $this->assertSame('Tool not found: supertool:^1.0.0.0', $exception->getMessage());
        $this->assertSame('supertool', $exception->getToolName());
        $this->assertSame('^1.0.0.0', $exception->getVersionConstraint());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
