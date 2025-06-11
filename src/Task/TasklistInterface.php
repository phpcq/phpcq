<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use IteratorAggregate;
use Phpcq\PluginApi\Version10\Task\TaskInterface;
use Traversable;

/** @extends IteratorAggregate<int, TaskInterface> */
interface TasklistInterface extends IteratorAggregate
{
    /**
     * Adds a task to the list.
     *
     * @param TaskInterface $taskRunner The task to add.
     */
    public function add(TaskInterface $taskRunner): void;

    /**
     * @return TaskInterface[]|iterable
     *
     * @psalm-return Traversable<int, TaskInterface>
     */
    #[\Override]
    public function getIterator(): Traversable;
}
