<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Buffer;

use Phpcq\Exception\RuntimeException;
use Phpcq\Report\Buffer\DiffBuffer;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Report\Buffer\DiffBuffer */
class DiffBufferTest extends TestCase
{
    public function testConstructionCreatesWithCorrectValues(): void
    {
        $buffer = new DiffBuffer('/absolute/path/name', 'local-name.ext');
        $this->assertSame('/absolute/path/name', $buffer->getAbsolutePath());
        $this->assertSame('local-name.ext', $buffer->getLocalName());
    }

    public function testConstructionFailsForNonAbsolutePath(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Absolute path expected but got: "./relative/path/name"');

        new DiffBuffer('./relative/path/name', 'local-name.ext');
    }
}
