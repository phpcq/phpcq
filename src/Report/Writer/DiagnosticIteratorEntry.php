<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Writer;

use Phpcq\Runner\Report\Buffer\DiagnosticBuffer;
use Phpcq\Runner\Report\Buffer\FileRangeBuffer;
use Phpcq\Runner\Report\Buffer\TaskReportBuffer;

final class DiagnosticIteratorEntry
{
    public function __construct(
        private readonly TaskReportBuffer $task,
        private readonly DiagnosticBuffer $diagnostic,
        private readonly ?FileRangeBuffer $range
    ) {
    }

    public function getTask(): TaskReportBuffer
    {
        return $this->task;
    }

    public function getDiagnostic(): DiagnosticBuffer
    {
        return $this->diagnostic;
    }

    public function getRange(): ?FileRangeBuffer
    {
        return $this->range;
    }

    public function isFileRelated(): bool
    {
        return null !== $this->range;
    }

    public function getFileName(): ?string
    {
        return null !== $this->range ? $this->range->getFile() : null;
    }
}
