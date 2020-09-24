<?php

declare(strict_types=1);

namespace Phpcq\Report\Writer;

use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\FileRangeBuffer;
use Phpcq\Report\Buffer\TaskReportBuffer;

final class DiagnosticIteratorEntry
{
    /** @var TaskReportBuffer */
    private $tool;
    /** @var DiagnosticBuffer */
    private $diagnostic;
    /** @var null|FileRangeBuffer */
    private $range;

    public function __construct(TaskReportBuffer $tool, DiagnosticBuffer $diagnostic, ?FileRangeBuffer $range)
    {
        $this->tool       = $tool;
        $this->diagnostic = $diagnostic;
        $this->range      = $range;
    }

    public function getTool(): TaskReportBuffer
    {
        return $this->tool;
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
