<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Phpcq\PluginApi\Version10\OutputInterface;
use Phpcq\PluginApi\Version10\OutputTransformerFactoryInterface as TransformerFactory;
use Phpcq\PluginApi\Version10\OutputTransformerInterface as Transformer;
use Phpcq\PluginApi\Version10\RuntimeException;
use Phpcq\PluginApi\Version10\Task\ReportWritingParallelTaskInterface;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Symfony\Component\Process\Process;
use Throwable;
use Traversable;

class ParallelizableProcessTask implements ReportWritingParallelTaskInterface
{
    /** @var string */
    private $toolName;

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

    /**
     * @param string                           $toolName    The name of the tool the task belongs to
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
     */
    public function __construct(
        string $toolName,
        array $command,
        TransformerFactory $transformer,
        int $cost,
        string $cwd = null,
        array $env = null,
        $input = null,
        ?float $timeout = 60
    ) {
        $this->toolName = $toolName;
        $this->command  = $command;
        $this->cost     = $cost;
        $this->cwd      = $cwd;
        $this->env      = $env;
        $this->input    = $input;
        $this->timeout  = $timeout;
        $this->factory  = $transformer;
    }

    public function getToolName(): string
    {
        return $this->toolName;
    }

    public function runWithReport(ToolReportInterface $report): void
    {
        $this->process = new Process($this->command, $this->cwd, $this->env, $this->input, $this->timeout);
        $command = $this->process->getCommandLine();
        $report->addDiagnostic(ToolReportInterface::SEVERITY_INFO, 'Executing: ' . $command);
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
        $this->errorOffset += strlen($latest);
        if (false === $latest) {
            return '';
        }

        return $latest;
    }
}
