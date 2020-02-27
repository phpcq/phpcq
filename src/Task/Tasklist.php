<?php

declare(strict_types=1);

namespace Phpcq\Task;

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
     */
    public function getIterator(): iterable
    {
        yield from $this->tasks;
    }
}