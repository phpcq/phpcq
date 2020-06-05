<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Buffer;

use Phpcq\Exception\RuntimeException;
use Phpcq\Report\Buffer\AttachmentBuffer;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Report\Buffer\AttachmentBuffer */
class AttachmentBufferTest extends TestCase
{
    public function testConstructionCreatesWithCorrectValues(): void
    {
        $buffer = new AttachmentBuffer('/absolute/path/name', 'local-name.ext', null);
        $this->assertSame('/absolute/path/name', $buffer->getAbsolutePath());
        $this->assertSame('local-name.ext', $buffer->getLocalName());
        $this->assertNull($buffer->getMimeType());
    }

    public function testConstructionCreatesWithMimeType(): void
    {
        $buffer = new AttachmentBuffer('/absolute/path/name', 'local-name.ext', 'application/octet-stream');
        $this->assertSame('/absolute/path/name', $buffer->getAbsolutePath());
        $this->assertSame('/absolute/path/name', $buffer->getAbsolutePath());
        $this->assertSame('application/octet-stream', $buffer->getMimeType());
    }

    public function testConstructionFailsForNonAbsolutePath(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Absolute path expected but got: "./relative/path/name"');

        new AttachmentBuffer('./relative/path/name', 'local-name.ext', null);
    }
}
