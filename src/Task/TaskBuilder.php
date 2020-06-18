<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Phpcq\OutputTransformer\ConsoleOutputTransformerFactory;
use Phpcq\PluginApi\Version10\OutputTransformerFactoryInterface;
use Phpcq\PluginApi\Version10\Task\TaskBuilderInterface;
use Phpcq\PluginApi\Version10\Task\TaskInterface;
use Traversable;

final class TaskBuilder implements TaskBuilderInterface
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

    /** @var bool */
    private $parallel = true;

    /**
     * Create a new instance.
     *
     * @param string[] $command
     */
    public function __construct(string $toolName, array $command)
    {
        $this->toolName = $toolName;
        $this->command = $command;
    }

    /**
     * @return self
     */
    public function withWorkingDirectory(string $cwd): TaskBuilderInterface
    {
        $this->cwd = $cwd;

        return $this;
    }

    /**
     * @param string[] $env
     *
     * @return self
     */
    public function withEnv(array $env): TaskBuilderInterface
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
    public function withInput($input): TaskBuilderInterface
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @param int|float $timeout
     *
     * @return self
     */
    public function withTimeout($timeout): TaskBuilderInterface
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @return self
     */
    public function withOutputTransformer(OutputTransformerFactoryInterface $factory): TaskBuilderInterface
    {
        $this->transformerFactory = $factory;

        return $this;
    }

    public function forceSingleProcess(): TaskBuilderInterface
    {
        $this->parallel = false;

        return $this;
    }

    public function build(): TaskInterface
    {
        $transformerFactory = $this->transformerFactory;
        if (null === $transformerFactory) {
            $transformerFactory = new ConsoleOutputTransformerFactory($this->toolName);
        }

        return new ProcessTask(
            $this->toolName,
            $this->command,
            $transformerFactory,
            $this->cwd,
            $this->env,
            $this->input,
            $this->timeout
        );
    }
}
