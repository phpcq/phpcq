<?php

declare(strict_types=1);

namespace Phpcq\Report;

use Phpcq\Plugin\PluginRegistry;
use Phpcq\PluginApi\Version10\ReportInterface;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Repository\RepositoryInterface;

final class Report implements ReportInterface
{
    /**
     * @var ReportBuffer
     */
    private $report;

    /** @var string */
    private $tempDir;

    /**
     * @var RepositoryInterface
     */
    private $installed;

    public function __construct(ReportBuffer $report, RepositoryInterface $installedTools, string $tempDir)
    {
        $this->report  = $report;
        $this->tempDir = $tempDir;
        $this->installed = $installedTools;
    }

    public function addToolReport(string $toolName): ToolReportInterface
    {
        $version = $this->installed->getTool($toolName, '*')->getVersion();
        return new ToolReport($this->report->createToolReport($toolName, $version), $this->tempDir);
    }
}
