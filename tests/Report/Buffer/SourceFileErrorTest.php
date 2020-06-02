<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Buffer;

use Phpcq\Report\Buffer\SourceFileDiagnostic;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Report\Buffer\SourceFileDiagnostic */
class SourceFileErrorTest extends TestCase
{
    public function testConstructionCreatesWithFilePosition(): void
    {
        $error = new SourceFileDiagnostic('error', 'This is an error', 'tool-name: section', 10, 20);
        $this->assertSame('error', $error->getSeverity());
        $this->assertSame('This is an error', $error->getMessage());
        $this->assertSame('tool-name: section', $error->getSource());
        $this->assertSame(10, $error->getLine());
        $this->assertSame(20, $error->getColumn());
    }

    public function testConstructionCreatesWithEmptyFilePosition(): void
    {
        $error = new SourceFileDiagnostic('error', 'This is an error', 'tool-name: section', null, null);

        $this->assertSame('error', $error->getSeverity());
        $this->assertSame('This is an error', $error->getMessage());
        $this->assertSame('tool-name: section', $error->getSource());
        $this->assertNull($error->getLine());
        $this->assertNull($error->getColumn());
    }
}
