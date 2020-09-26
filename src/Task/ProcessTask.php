<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\Output\OutputTransformerFactoryInterface as TransformerFactory;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\PluginApi\Version10\Task\OutputWritingTaskInterface;
use Phpcq\PluginApi\Version10\Task\ReportWritingTaskInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Symfony\Component\Process\Process;
use Throwable;
use Traversable;

use function implode;

/**
 * This task runner executes a process.
 */
class ProcessTask implements ReportWritingTaskInterface, OutputWritingTaskInterface
{
    /** @var string */
    private $taskName;

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
     * @var TransformerFactory
     */
    private $transformer;

    /** @var array<string,string> */
    private $metadata;

    /**
     * @param string                           $taskName    The name of the tool the task belongs to
     * @param string[] $command                             The command to run and its arguments listed as separate
     *                                                      entries
     * @param TransformerFactory               $transformer The output transformer
     * @param string|null                      $cwd         The working directory or null to use the working dir of the
     *                                                      current PHP process
     * @param string[]|null $env                            The environment variables or null to use the same
     *                                                      environment
     *                                                      as the current PHP process
     * @param resource|string|Traversable|null $input       The input as stream resource, scalar or \Traversable, or
     *                                                      null for no input
     * @param int|float|null                   $timeout     The timeout in seconds or null to disable
     * @param array<string,string>             $metadata    Process metadata
     */
    public function __construct(
        string $taskName,
        array $command,
        TransformerFactory $transformer,
        string $cwd = null,
        array $env = null,
        $input = null,
        ?float $timeout = 60,
        array $metadata = []
    ) {
        $this->taskName    = $taskName;
        $this->command     = $command;
        $this->cwd         = $cwd;
        $this->env         = $env;
        $this->input       = $input;
        $this->timeout     = $timeout;
        $this->transformer = $transformer;
        $this->metadata    = $metadata;
    }

    public function getToolName(): string
    {
        return $this->taskName;
    }

    public function runWithReport(TaskReportInterface $report): void
    {
        foreach ($this->metadata as $key => $value) {
            $report->addMetadata($key, $value);
        }

        $command = implode(' ', $this->command);
        $report->addDiagnostic(TaskReportInterface::SEVERITY_INFO, 'Executing: ' . $command);

        $process     = new Process($this->command, $this->cwd, $this->env, $this->input, $this->timeout);
        $transformer = $this->transformer->createFor($report);

        try {
            $process->run(static function (string $type, string $data) use ($transformer) {
                switch ($type) {
                    case Process::ERR:
                        $transformer->write($data, OutputInterface::CHANNEL_STDERR);
                        return;
                    case Process::OUT:
                        $transformer->write($data, OutputInterface::CHANNEL_STDOUT);
                        return;
                }
            });
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Process failed with exit code ' . (string) $process->getExitCode() . ': ' . $process->getCommandLine(),
                (int) $exception->getCode(),
                $exception
            );
        } finally {
            $transformer->finish((int) $process->getExitCode());
        }

        if ($report->getStatus() !== TaskReportInterface::STATUS_PASSED) {
            throw new RuntimeException(
                'Tool report did not pass (Status ' . $report->getStatus() . '). Process exited code '
                . (string) $process->getExitCode() . ': ' . $command,
                (int) $process->getExitCode()
            );
        }
    }

    public function runForOutput(OutputInterface $output): void
    {
        $process = new Process($this->command, $this->cwd, $this->env, $this->input, $this->timeout);
        $output->writeln('', OutputInterface::VERBOSITY_VERBOSE, OutputInterface::CHANNEL_STDERR);
        $output->writeln(
            'Executing: ' . $process->getCommandLine(),
            OutputInterface::VERBOSITY_VERBOSE,
            OutputInterface::CHANNEL_STDERR
        );
        $output->writeln('', OutputInterface::VERBOSITY_VERBOSE, OutputInterface::CHANNEL_STDERR);

        try {
            // Fixme: Move fail handling to the processor
            $process->mustRun(function (string $type, string $data) use ($output) {
                switch ($type) {
                    case Process::ERR:
                        $output->write($data, OutputInterface::VERBOSITY_NORMAL, OutputInterface::CHANNEL_STDERR);
                        return;
                    case Process::OUT:
                        $output->write($data);
                        return;
                }
            });
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Process failed with exit code ' . (string) $process->getExitCode() . ': ' . $process->getCommandLine(),
                (int) $exception->getCode(),
                $exception
            );
        }
    }
}
