<?php

declare(strict_types=1);

namespace Phpcq\Task;

use IteratorAggregate;

interface TasklistInterface extends IteratorAggregate
{
    public function add(TaskRunnerInterface $taskRunner): void;

    /**
     * @return TaskRunnerInterface[]|iterable
     */
    public function getIterator(): iterable;
}
