<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Phpcq\OutputTransformer\ConsoleOutputTransformerFactory;
use Phpcq\PluginApi\Version10\OutputTransformerFactoryInterface;
use Phpcq\PluginApi\Version10\ReportInterface;
use Phpcq\PluginApi\Version10\TaskRunnerBuilderInterface;
use Phpcq\PluginApi\Version10\TaskRunnerInterface;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Traversable;

use function assert;

final class TaskRunnerBuilder implements TaskRunnerBuilderInterface
{
    /**
     * @var string
     */
    private $toolName;

    /**
     * @var string[]
     */
    private $command;

    /**
     * @var string|null
     */
    private $cwd = null;

    /**
     * @var string[]|null
     */
    private $env = null;

    /**
     * @var resource|string|Traversable|null
     */
    private $input = null;

    /**
     * @var int|float|null
     */
    private $timeout = null;

    /**
     * @var OutputTransformerFactoryInterface|null
     */
    private $transformerFactory;

    /**
     * @var ToolReportInterface|null
     */
    private $report;

    /**
     * Create a new instance.
     *
     * @param string[] $command
     */
    public function __construct(string $toolName, array $command, ?ToolReportInterface $report)
    {
        $this->toolName = $toolName;
        $this->command = $command;
        $this->report = $report;
    }

    /**
     * @return self
     */
    public function withWorkingDirectory(string $cwd): TaskRunnerBuilderInterface
    {
        $this->cwd = $cwd;

        return $this;
    }

    /**
     * @param string[] $env
     *
     * @return self
     */
    public function withEnv(array $env): TaskRunnerBuilderInterface
    {
        $this->env = $env;

        return $this;
    }

    /**
     * @param resource|string|Traversable $input The input as stream resource, scalar or \Traversable, or null for no
     *                                           input
     *
     * @return self
     */
    public function withInput($input): TaskRunnerBuilderInterface
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @param int|float $timeout
     *
     * @return self
     */
    public function withTimeout($timeout): TaskRunnerBuilderInterface
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @return self
     */
    public function withOutputTransformer(OutputTransformerFactoryInterface $factory): TaskRunnerBuilderInterface
    {
        $this->transformerFactory = $factory;

        return $this;
    }

    public function build(): TaskRunnerInterface
    {
        if ($this->report === null) {
            return $this->buildExecTaskRunner();
        }

        return $this->buildProcessTaskRunner();
    }

    private function buildProcessTaskRunner(): TaskRunnerInterface
    {
        assert($this->report instanceof ReportInterface);

        $transformerFactory = $this->transformerFactory;
        if (null === $transformerFactory) {
            $transformerFactory = new ConsoleOutputTransformerFactory($this->toolName);
        }

        return new ProcessTaskRunner(
            $this->command,
            $this->report,
            $transformerFactory,
            $this->cwd,
            $this->env,
            $this->input,
            $this->timeout
        );
    }

    private function buildExecTaskRunner(): TaskRunnerInterface
    {
        return new ExecTaskRunner(
            $this->command,
            $this->cwd,
            $this->env,
            $this->input,
            $this->timeout
        );
    }
}
