<?php

declare(strict_types=1);

namespace Phpcq\Task;

use IteratorAggregate;
use Phpcq\PluginApi\Version10\TaskRunnerInterface;
use Traversable;

interface TasklistInterface extends IteratorAggregate
{
    public function add(TaskRunnerInterface $taskRunner): void;

    /**
     * @return TaskRunnerInterface[]|iterable
     *
     * @psalm-return Traversable<int, TaskRunnerInterface>
     */
    public function getIterator(): iterable;
}
