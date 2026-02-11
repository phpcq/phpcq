<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Report;

use Phpcq\PluginApi\Version10\Report\DiagnosticBuilderInterface;
use Phpcq\Runner\Report\Buffer\FileRangeBuffer;
use Phpcq\Runner\Report\FileDiagnosticBuilder;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Report\FileDiagnosticBuilder */
final class FileDiagnosticBuilderTest extends TestCase
{
    public function testBuildWithoutRanges(): void
    {
        $files = null;
        $parent = $this->createMock(DiagnosticBuilderInterface::class);
        $builder = new FileDiagnosticBuilder(
            $parent,
            'some/file',
            function (array $rangeBuffers) use (&$files) {
                $files = $rangeBuffers;
            }
        );
        $this->assertSame($parent, $builder->end());

        $this->assertIsArray($files);
        $this->assertCount(1, $files);

        $this->assertInstanceOf(FileRangeBuffer::class, $files[0]);
        /** @var FileRangeBuffer $range */
        $range = $files[0];
        $this->assertSame('some/file', $range->getFile());
        $this->assertNull($range->getStartLine());
        $this->assertNull($range->getStartColumn());
        $this->assertNull($range->getEndLine());
        $this->assertNull($range->getEndColumn());
    }

    public function testBuildsWithRanges(): void
    {
        $files = null;
        $parent = $this->createMock(DiagnosticBuilderInterface::class);
        $builder = new FileDiagnosticBuilder(
            $parent,
            'some/file',
            function (array $rangeBuffers) use (&$files) {
                $files = $rangeBuffers;
            }
        );

        $this->assertSame($builder, $builder->forRange(1, 2, 10, 20));
        $this->assertSame($builder, $builder->forRange(3, 4, 30, 40));

        $this->assertSame($parent, $builder->end());

        $this->assertIsArray($files);
        $this->assertCount(2, $files);

        $this->assertInstanceOf(FileRangeBuffer::class, $files[0]);
        /** @var FileRangeBuffer $range */
        $range = $files[0];
        $this->assertSame('some/file', $range->getFile());
        $this->assertSame(1, $range->getStartLine());
        $this->assertSame(2, $range->getStartColumn());
        $this->assertSame(10, $range->getEndLine());
        $this->assertSame(20, $range->getEndColumn());

        $this->assertInstanceOf(FileRangeBuffer::class, $files[1]);
        /** @var FileRangeBuffer $range */
        $range = $files[1];
        $this->assertSame('some/file', $range->getFile());
        $this->assertSame(3, $range->getStartLine());
        $this->assertSame(4, $range->getStartColumn());
        $this->assertSame(30, $range->getEndLine());
        $this->assertSame(40, $range->getEndColumn());
    }
}
