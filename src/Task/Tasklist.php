<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use Phpcq\PluginApi\Version10\Task\TaskInterface;
use Traversable;

/**
 * Default task list implementation.
 */
class Tasklist implements TasklistInterface
{
    /**
     * @var TaskInterface[]
     */
    private $tasks = [];

    #[\Override]
    public function add(TaskInterface $taskRunner): void
    {
        $this->tasks[] = $taskRunner;
    }

    /**
     * @return TaskInterface[]|iterable
     *
     * @psalm-return \Generator<array-key, TaskInterface, mixed, void>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        yield from $this->tasks;
    }
}
