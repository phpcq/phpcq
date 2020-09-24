<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use Phpcq\PluginApi\Version10\Task\TaskInterface;

/**
 * Default task list implementation.
 */
class Tasklist implements TasklistInterface
{
    /**
     * @var TaskInterface[]
     */
    private $tasks = [];

    public function add(TaskInterface $taskRunner): void
    {
        $this->tasks[] = $taskRunner;
    }

    /**
     * @return TaskInterface[]|iterable
     *
     * @psalm-return \Generator<array-key, TaskInterface, mixed, void>
     */
    public function getIterator(): iterable
    {
        yield from $this->tasks;
    }
}
