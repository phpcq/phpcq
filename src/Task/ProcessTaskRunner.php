<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Phpcq\PluginApi\Version10\OutputInterface;
use Phpcq\PluginApi\Version10\PostProcessorInterface;
use Phpcq\PluginApi\Version10\RuntimeException;
use Phpcq\PluginApi\Version10\TaskRunnerInterface;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Symfony\Component\Process\Process;
use Traversable;

/**
 * This task runner executes a process.
 */
class ProcessTaskRunner implements TaskRunnerInterface
{
    /**
     * @var string[]
     */
    private $command;

    /**
     * @var string|null
     */
    private $cwd;

    /**
     * @var string[]|null
     */
    private $env;

    /**
     * @var resource|string|Traversable|null
     */
    private $input;

    /**
     * @var int|float|null
     */
    private $timeout;

    /**
     * @var ToolReportInterface
     */
    private $report;

    /**
     * @var PostProcessorInterface
     */
    private $postProcessor;

    /**
     * @param string[]                         $command The command to run and its arguments listed as separate entries
     * @param ToolReportInterface              $report
     * @param PostProcessorInterface           $postProcessor
     * @param string|null                      $cwd     The working directory or null to use the working dir of the
     *                                                  current PHP process
     * @param string[]|null                    $env     The environment variables or null to use the same environment as
     *                                                  the current PHP process
     * @param resource|string|Traversable|null $input   The input as stream resource, scalar or \Traversable, or null
     *                                                  for no input
     * @param int|float|null                   $timeout The timeout in seconds or null to disable
     *
     */
    public function __construct(
        array $command,
        ToolReportInterface $report,
        PostProcessorInterface $postProcessor,
        string $cwd = null,
        array $env = null,
        $input = null,
        ?float $timeout = 60
    ) {
        $this->command       = $command;
        $this->cwd           = $cwd;
        $this->env           = $env;
        $this->input         = $input;
        $this->timeout       = $timeout;
        $this->report        = $report;
        $this->postProcessor = $postProcessor;
    }

    public function run(OutputInterface $output): void
    {
        $process = new Process($this->command, $this->cwd, $this->env, $this->input, $this->timeout);
        $output->writeln('', OutputInterface::VERBOSITY_VERBOSE, OutputInterface::CHANNEL_STRERR);
        $output->writeln(
            'Executing: ' . $process->getCommandLine(),
            OutputInterface::VERBOSITY_VERBOSE,
            OutputInterface::CHANNEL_STRERR
        );
        $output->writeln('', OutputInterface::VERBOSITY_VERBOSE, OutputInterface::CHANNEL_STRERR);
        $consoleOutput = [
            OutputInterface::CHANNEL_STDOUT => '',
            OutputInterface::CHANNEL_STRERR => '',
        ];

        try {
            $process->mustRun(function (string $type, string $data) use ($output, &$consoleOutput) {
                switch ($type) {
                    case Process::ERR:
                        $output->write($data, OutputInterface::VERBOSITY_NORMAL, OutputInterface::CHANNEL_STRERR);
                        $consoleOutput[OutputInterface::CHANNEL_STRERR] .= $data;
                        return;
                    case Process::OUT:
                        $output->write($data);
                        $consoleOutput[OutputInterface::CHANNEL_STDOUT] .= $data;
                        return;
                }
            });
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'Process failed with exit code ' . (string) $process->getExitCode() . ': ' . $process->getCommandLine(),
                (int) $exception->getCode(),
                $exception
            );
        } finally {
            // FIXME: we should not buffer these as attachment - the post processor should do it!
            if ('' !== ($stdOut = $consoleOutput[OutputInterface::CHANNEL_STRERR])) {
                $this->report->addBufferAsAttachment($stdOut, 'stderr.log');
            }
            if ('' !== ($stdErr = $consoleOutput[OutputInterface::CHANNEL_STDOUT])) {
                $this->report->addBufferAsAttachment($stdErr, 'stdout.log');
            }
            // FIXME: we have to pass stdErr also.
            $this->postProcessor->process($this->report, $stdOut, (int) $process->getExitCode(), $output);
        }
    }
}
