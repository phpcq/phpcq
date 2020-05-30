<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Buffer;

use Phpcq\Report\Buffer\SourceFileBuffer;
use Phpcq\Report\Buffer\SourceFileError;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Report\Buffer\SourceFileBuffer */
class SourceFileBufferTest extends TestCase
{
    public function testConstructionCreatesEmpty(): void
    {
        $buffer = new SourceFileBuffer('src/some/php/file.php');
        $this->assertSame('src/some/php/file.php', $buffer->getFilePath());
        $this->assertEmpty(iterator_to_array($buffer->getIterator()));
    }

    public function testAddsErrors(): void
    {
        $buffer = new SourceFileBuffer('src/some/php/file.php');

        $buffer->addError('error', 'This is an error', 'tool-name: section', 10, 20);

        $errors = iterator_to_array($buffer->getIterator());
        $this->assertCount(1, $errors);
        $this->arrayHasKey(0);
        $error = $errors[0];
        $this->assertInstanceOf(SourceFileError::class, $error);
        /** @var SourceFileError $error */


        $this->assertSame('error', $error->getSeverity());
        $this->assertSame('This is an error', $error->getMessage());
        $this->assertSame('tool-name: section', $error->getSource());
        $this->assertSame(10, $error->getLine());
        $this->assertSame(20, $error->getColumn());
    }

    public function testAddsErrorsWithNulledFilePosition(): void
    {
        $buffer = new SourceFileBuffer('src/some/php/file.php');

        $buffer->addError('error', 'This is an error', null, null, null);

        $errors = iterator_to_array($buffer->getIterator());
        $this->assertCount(1, $errors);
        $this->arrayHasKey(0);
        $error = $errors[0];
        $this->assertInstanceOf(SourceFileError::class, $error);
        /** @var SourceFileError $error */

        $this->assertSame('error', $error->getSeverity());
        $this->assertSame('This is an error', $error->getMessage());
        $this->assertNull($error->getSource());
        $this->assertNull($error->getLine());
        $this->assertNull($error->getColumn());
    }
}
