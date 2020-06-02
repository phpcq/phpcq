<?php

declare(strict_types=1);

namespace Phpcq\PostProcessor;

use Phpcq\PluginApi\Version10\OutputInterface;
use Phpcq\PluginApi\Version10\PostProcessorInterface;
use Phpcq\PluginApi\Version10\ToolReportInterface;

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
    public function process(
        ToolReportInterface $report,
        string $consoleOutput,
        int $exitCode,
        OutputInterface $output
    ): void {
        if (0 !== $exitCode) {
            $report->addDiagnostic('error', $consoleOutput);
        } else {
            $report->addDiagnostic('info', $consoleOutput);
        }

        $report->finish(0 === $exitCode ? ToolReportInterface::STATUS_PASSED : ToolReportInterface::STATUS_FAILED);
    }
}
