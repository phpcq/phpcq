<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Exception;

use Phpcq\Runner\Exception\InvalidHashException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Exception\InvalidHashException
 */
class InvalidHashExceptionTest extends TestCase
{
    public function testInitialization(): void
    {
        $exception = new InvalidHashException('unknown', 'hash-value');
        $this->assertSame('unknown', $exception->getHashType());
        $this->assertSame('hash-value', $exception->getHashValue());
        $this->assertSame('Invalid hash type: unknown (hash-value)', $exception->getMessage());
    }
}
