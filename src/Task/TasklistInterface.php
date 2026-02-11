<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use IteratorAggregate;
use Override;
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
     * @return Traversable<int, TaskInterface>
     */
    #[Override]
    public function getIterator(): Traversable;
}
