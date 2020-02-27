<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Traversable;

final class TaskRunnerBuilder
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
     * Create a new instance.
     *
     * @param string[] $command
     */
    public function __construct(array $command)
    {
        $this->command = $command;
    }

    public function withWorkingDirectory(string $cwd): self
    {
        $this->cwd = $cwd;

        return $this;
    }

    /**
     * @param string[] $cwd
     */
    public function withEnv(array $env): self
    {
        $this->env = $env;

        return $this;
    }

    /**
     * @param resource|string|Traversable $input The input as stream resource, scalar or \Traversable, or null for no input
     */
    public function withInput($input): self
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @var int|float
     */
    public function withTimeout($timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function build(): TaskRunnerInterface
    {
        return new ProcessTaskRunner($this->command, $this->cwd, $this->env, $this->input, $this->timeout);
    }
}