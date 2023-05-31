<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Writer;

use Phpcq\Runner\Report\Buffer\ReportBuffer;

interface ReportWriterInterface
{
    public static function writeReport(string $targetPath, ReportBuffer $report, string $minimumSeverity): void;
}
