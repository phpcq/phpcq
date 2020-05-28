<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Phpcq\PluginApi\Version10\TaskRunnerBuilderInterface;
use Phpcq\PluginApi\Version10\TaskRunnerInterface;
use Phpcq\PostProcessor\CheckstyleFilePostProcessor;
use Phpcq\PostProcessor\PostProcessorInterface;
use Phpcq\Report\Report;
use Traversable;

final class TaskRunnerBuilder implements TaskRunnerBuilderInterface
{
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
     * @var PostProcessorInterface|null
     */
    private $postProcessor;

    /**
     * @var Report
     */
    private $report;

    /**
     * Create a new instance.
     *
     * @param string[] $command
     */
    public function __construct(array $command, Report $report)
    {
        $this->command = $command;
        $this->report = $report;
    }

    public function withWorkingDirectory(string $cwd): TaskRunnerBuilderInterface
    {
        $this->cwd = $cwd;

        return $this;
    }

    /**
     * @param string[] $env
     */
    public function withEnv(array $env): TaskRunnerBuilderInterface
    {
        $this->env = $env;

        return $this;
    }

    /**
     * @param resource|string|Traversable $input The input as stream resource, scalar or \Traversable, or null for no
     *                                           input
     */
    public function withInput($input): TaskRunnerBuilderInterface
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @param int|float $timeout
     */
    public function withTimeout($timeout): TaskRunnerBuilderInterface
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function withPostProcessor(PostProcessorInterface $postProcessor): TaskRunnerBuilderInterface
    {
        $this->postProcessor = $postProcessor;

        return $this;
    }

    public function withCheckstyleFilePostProcessor(
        string $toolName,
        string $checkstyleFile
    ): TaskRunnerBuilderInterface {
        $this->postProcessor = new CheckstyleFilePostProcessor($toolName, $checkstyleFile);

        return $this;
    }

    public function build(): TaskRunnerInterface
    {
        return new ProcessTaskRunner(
            $this->command,
            $this->report,
            $this->postProcessor,
            $this->cwd,
            $this->env,
            $this->input,
            $this->timeout
        );
    }
}
