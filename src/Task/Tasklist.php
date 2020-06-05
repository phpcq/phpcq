<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Phpcq\PluginApi\Version10\TaskRunnerInterface;

/**
 * Default task list implementation.
 */
class Tasklist implements TasklistInterface
{
    /**
     * @var TaskRunnerInterface[]
     */
    private $tasks = [];

    public function add(TaskRunnerInterface $taskRunner): void
    {
        $this->tasks[] = $taskRunner;
    }

    /**
     * @return TaskRunnerInterface[]|iterable
     *
     * @psalm-return \Generator<array-key, TaskRunnerInterface, mixed, void>
     */
    public function getIterator(): iterable
    {
        yield from $this->tasks;
    }
}
