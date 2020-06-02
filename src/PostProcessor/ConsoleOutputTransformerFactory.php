<?php

declare(strict_types=1);

namespace Phpcq\PostProcessor;

use Phpcq\PluginApi\Version10\OutputTransformerFactoryInterface;
use Phpcq\PluginApi\Version10\OutputTransformerInterface;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\PluginApi\Version10\Util\BufferedLineReader;

final class ConsoleOutputTransformerFactory implements OutputTransformerFactoryInterface
{
    /**
     * @var string
     */
    private $toolName;

    /**
     * ConsoleOutputTransformerFactory constructor.
     *
     * @param string $toolName
     */
    public function __construct(string $toolName)
    {
        $this->toolName = $toolName;
    }

    public function createFor(ToolReportInterface $report): OutputTransformerInterface
    {
        return new class ($report) implements OutputTransformerInterface {
            /** @var ToolReportInterface */
            private $report;
            /** @var BufferedLineReader */
            private $data;

            /**
             * Create a new instance.
             */
            public function __construct(ToolReportInterface $report)
            {
                $this->report = $report;
                $this->data   = new BufferedLineReader();
            }

            public function write(string $data, int $channel): void
            {
                $this->data->push($data);
            }

            public function finish(int $exitCode): void
            {
                $content = '';
                while ($line = $this->data->fetch()) {
                    $content .= $line;
                }

                [$status, $severity] = $this->calculateStatusAndSeverity($exitCode);
                $this->report->addDiagnostic($severity, $content);
                $this->report->finish($status);
            }

            private function calculateStatusAndSeverity(int $exitCode): array
            {
                if (0 === $exitCode) {
                    return [
                        ToolReportInterface::STATUS_PASSED,
                        ToolReportInterface::SEVERITY_INFO,
                    ];
                }
                return [
                    ToolReportInterface::STATUS_FAILED,
                    ToolReportInterface::SEVERITY_ERROR,
                ];
            }
        };
    }
}
