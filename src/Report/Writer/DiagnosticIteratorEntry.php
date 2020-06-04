<?php

declare(strict_types=1);

namespace Phpcq\Report\Writer;

use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\FileRangeBuffer;
use Phpcq\Report\Buffer\ToolReportBuffer;

final class DiagnosticIteratorEntry
{
    /** @var ToolReportBuffer */
    private $tool;
    /** @var DiagnosticBuffer */
    private $diagnostic;
    /** @var null|FileRangeBuffer */
    private $range;

    public function __construct(ToolReportBuffer $tool, DiagnosticBuffer $diagnostic, ?FileRangeBuffer $range)
    {
        $this->tool       = $tool;
        $this->diagnostic = $diagnostic;
        $this->range      = $range;
    }

    public function getTool(): ToolReportBuffer
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
