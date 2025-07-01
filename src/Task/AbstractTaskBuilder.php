<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use Override;
use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Output\OutputTransformerFactoryInterface;
use Phpcq\PluginApi\Version10\Task\TaskBuilderInterface;
use Phpcq\PluginApi\Version10\Task\TaskInterface;
use Phpcq\Runner\OutputTransformer\ConsoleOutputTransformerFactory;
use Traversable;

abstract class AbstractTaskBuilder implements TaskBuilderInterface
{
    private ?string $cwd = null;

    /**
     * @var array<string, string>|null
     */
    private ?array $env = null;

    /**
     * @var resource|string|Traversable|null
     */
    private mixed $input = null;

    private int|float|null $timeout = null;

    private ?OutputTransformerFactoryInterface $transformerFactory = null;

    private bool $parallel = true;

    private int $cost = 1;

    private bool $tty = false;

    /**
     * Create a new instance.
     *
     * @param array<string,string> $metadata
     */
    public function __construct(private readonly string $taskName, private readonly array $metadata)
    {
    }

    #[Override]
    public function withWorkingDirectory(string $cwd): TaskBuilderInterface
    {
        $this->cwd = $cwd;

        return $this;
    }

    /**
     * @param array<string, string> $env
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    #[Override]
    public function withEnv(array $env): TaskBuilderInterface
    {
        $this->env = $env;

        return $this;
    }

    /**
     * @param resource|string|Traversable $input The input as a stream resource, scalar or \Traversable, or null
     *                                                   for no input
     */
    #[Override]
    public function withInput($input): TaskBuilderInterface
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @param int|float $timeout
     */
    #[Override]
    public function withTimeout($timeout): TaskBuilderInterface
    {
        $this->timeout = $timeout;

        return $this;
    }

    #[Override]
    public function withOutputTransformer(OutputTransformerFactoryInterface $factory): TaskBuilderInterface
    {
        $this->transformerFactory = $factory;

        return $this;
    }

    #[Override]
    public function forceSingleProcess(): TaskBuilderInterface
    {
        if (1 !== $this->cost) {
            throw new RuntimeException('Can not force task with cost > 1 to run as single process');
        }

        $this->parallel = false;

        return $this;
    }

    #[Override]
    public function withCosts(int $cost): TaskBuilderInterface
    {
        if (!$this->parallel && $cost !== 1) {
            throw new RuntimeException('Can not set cost for single process forced task.');
        }
        if (0 > $cost) {
            throw new RuntimeException('Cost must be greater than zero.');
        }

        $this->cost = $cost;

        return $this;
    }

    /** @return $this */
    public function withTty(): self
    {
        $this->tty = true;

        return $this;
    }

    /** @return list<string> */
    abstract protected function buildCommand(): array;

    #[Override]
    public function build(): TaskInterface
    {
        $transformerFactory = $this->transformerFactory;
        if (null === $transformerFactory) {
            $transformerFactory = new ConsoleOutputTransformerFactory($this->taskName);
        }

        if ($this->parallel) {
            return new ParallelizableProcessTask(
                $this->taskName,
                $this->buildCommand(),
                $transformerFactory,
                $this->cost,
                $this->cwd,
                $this->env,
                $this->input,
                $this->timeout,
                $this->metadata,
            );
        }

        return new ProcessTask(
            $this->taskName,
            $this->buildCommand(),
            $transformerFactory,
            $this->cwd,
            $this->env,
            $this->input,
            $this->timeout,
            $this->metadata,
            $this->tty,
        );
    }
}
