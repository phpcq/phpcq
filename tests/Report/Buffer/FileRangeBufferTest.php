<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Buffer;

use Phpcq\Report\Buffer\FileRangeBuffer;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Report\Buffer\FileRangeBuffer */
final class FileRangeBufferTest extends TestCase
{
    public function testConstructionCreatesWithFilePosition(): void
    {
        $rangeBuffer = new FileRangeBuffer('/file/name', 1, 2, 10, 20);
        $this->assertSame('/file/name', $rangeBuffer->getFile());
        $this->assertSame(1, $rangeBuffer->getStartLine());
        $this->assertSame(2, $rangeBuffer->getStartColumn());
        $this->assertSame(10, $rangeBuffer->getEndLine());
        $this->assertSame(20, $rangeBuffer->getEndColumn());
    }

    public function testConstructionCreatesWithEmptyFilePosition(): void
    {
        $rangeBuffer = new FileRangeBuffer('/file/name', null, null, null, null);
        $this->assertSame('/file/name', $rangeBuffer->getFile());
        $this->assertNull($rangeBuffer->getStartLine());
        $this->assertNull($rangeBuffer->getStartColumn());
        $this->assertNull($rangeBuffer->getEndLine());
        $this->assertNull($rangeBuffer->getEndColumn());
    }
}
