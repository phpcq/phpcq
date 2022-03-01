<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\Output\OutputTransformerFactoryInterface as TransformerFactory;
use Phpcq\PluginApi\Version10\Output\OutputTransformerInterface as Transformer;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\PluginApi\Version10\Task\ReportWritingParallelTaskInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Symfony\Component\Process\Process;
use Throwable;
use Traversable;

class ParallelizableProcessTask implements ReportWritingParallelTaskInterface
{
    /** @var string */
    private $taskName;

    /** @var string[] */
    private $command;

    /** @var int */
    private $cost;

    /** @var string|null */
    private $cwd;

    /** @var string[]|null */
    private $env;

    /** @var resource|string|Traversable|null */
    private $input;

    /** @var int|float|null */
    private $timeout;

    /** @var TransformerFactory */
    private $factory;

    /** @var Transformer|null */
    private $transformer;

    /** @var Process|null */
    private $process;

    /** @var int|null */
    private $errorOffset;

    /** @var array<string,string> */
    private $metadata;

    /**
     * @param string                           $taskName    The name of the task
     * @param string[]                         $command     The command to run and its arguments listed as separate
     *                                                      entries
     * @param TransformerFactory               $transformer The output transformer
     * @param string|null                      $cwd         The working directory or null to use the working dir of the
     *                                                      current PHP process
     * @param string[]|null                    $env         The environment variables or null to use the same
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
        int $cost,
        string $cwd = null,
        array $env = null,
        $input = null,
        ?float $timeout = 60,
        array $metadata = []
    ) {
        $this->taskName = $taskName;
        $this->command  = $command;
        $this->cost     = $cost;
        $this->cwd      = $cwd;
        $this->env      = $env;
        $this->input    = $input;
        $this->timeout  = $timeout;
        $this->factory  = $transformer;
        $this->metadata = $metadata;
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

        $this->process = new Process($this->command, $this->cwd, $this->env, $this->input, $this->timeout);
        $command = $this->process->getCommandLine();
        $report->addDiagnostic(TaskReportInterface::SEVERITY_INFO, 'Executing: ' . $command);
        $this->transformer = $this->factory->createFor($report);
        $this->errorOffset = 0;
        try {
            $this->process->start();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Process failed with exit code ' . (string) $this->process->getExitCode() . ': ' . $command,
                (int) $exception->getCode(),
                $exception
            );
        }
    }

    public function tick(): bool
    {
        assert($this->process instanceof Process);
        assert($this->transformer instanceof Transformer);

        $data = $this->getIncrementalErrorOutput();
        $this->transformer->write($data, OutputInterface::CHANNEL_STDERR);

        $data = $this->process->getIncrementalOutput();
        $this->transformer->write($data, OutputInterface::CHANNEL_STDOUT);

        $finished = !$this->process->isRunning();

        if ($finished) {
            $this->transformer->finish((int) $this->process->getExitCode());
        }

        return !$finished;
    }

    public function getCost(): int
    {
        return $this->cost;
    }

    /**
     * Sadly no Process::getIncrementalErrorOutput() - so we fake one.
     *
     * @return string
     */
    public function getIncrementalErrorOutput(): string
    {
        assert($this->process instanceof Process);
        assert(is_int($this->errorOffset));
        $all                = $this->process->getErrorOutput();
        $latest             = substr($all, $this->errorOffset);
        if (false === $latest) {
            return '';
        }
        $this->errorOffset += strlen($latest);

        return $latest;
    }
}
