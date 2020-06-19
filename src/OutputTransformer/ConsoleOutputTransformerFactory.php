<?php

declare(strict_types=1);

namespace Phpcq\OutputTransformer;

use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\Output\OutputTransformerFactoryInterface;
use Phpcq\PluginApi\Version10\Output\OutputTransformerInterface;
use Phpcq\PluginApi\Version10\Report\ToolReportInterface;
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

    /** @SuppressWarnings(PHPMD.UnusedLocalVariable) */
    public function createFor(ToolReportInterface $report): OutputTransformerInterface
    {
        return new class ($report) implements OutputTransformerInterface {
            /** @var ToolReportInterface */
            private $report;
            /** @var BufferedLineReader */
            private $data;
            /** @var string */
            private $stdErr = '';
            /** @var string */
            private $stdOut = '';

            /**
             * Create a new instance.
             */
            public function __construct(ToolReportInterface $report)
            {
                $this->report = $report;
                $this->data   = BufferedLineReader::create();
            }

            public function write(string $data, int $channel): void
            {
                $this->data->push($data);
                if (OutputInterface::CHANNEL_STDOUT === $channel) {
                    $this->stdOut .= $data;
                    return;
                }
                if (OutputInterface::CHANNEL_STDERR === $channel) {
                    $this->stdErr .= $data;
                    return;
                }
            }

            public function finish(int $exitCode): void
            {
                $content = '';
                while (null !== ($line = $this->data->fetch())) {
                    if ($content !== '') {
                        $content .= "\n";
                    }
                    $content .= $line;
                }

                [$status, $severity] = $this->calculateStatusAndSeverity($exitCode);
                $this->report->addDiagnostic($severity, $content)->end();

                if ('' !== $this->stdErr) {
                    $this->report->addAttachment('stderr.log')->fromString($this->stdErr)->end();
                }
                if ('' !== $this->stdOut) {
                    $this->report->addAttachment('stdout.log')->fromString($this->stdOut)->end();
                }

                $this->report->close($status);
            }

            /**
             * @return string[]
             *
             * @psalm-return array{0: string, 1: string}
             */
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
                    ToolReportInterface::SEVERITY_MAJOR,
                ];
            }
        };
    }
}
