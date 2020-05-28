<?php

declare(strict_types=1);

namespace Phpcq\PostProcessor;

use Phpcq\PluginApi\Version10\OutputInterface;
use Phpcq\PluginApi\Version10\PostProcessorInterface;
use Phpcq\PluginApi\Version10\ReportInterface;
use Phpcq\Report\Report;

use function implode;

final class ConsoleOutputToolReportProcessor implements PostProcessorInterface
{
    /**
     * @var string
     */
    private $toolName;

    /**
     * ConsoleOutputToolReportProcessor constructor.
     *
     * @param string $toolName
     */
    public function __construct(string $toolName)
    {
        $this->toolName = $toolName;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process(ReportInterface $report, array $consoleOutput, int $exitCode, OutputInterface $output): void
    {
        $report->addToolReport(
            $this->toolName,
            $exitCode === 0 ? Report::STATUS_PASSED : Report::STATUS_FAILED,
            implode("\n", $consoleOutput),
        );
    }
}
