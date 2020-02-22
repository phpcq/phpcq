<?php

declare(strict_types=1);

namespace Phpcq\Task;

use IteratorAggregate;

interface TasklistInterface extends IteratorAggregate
{
    public function add(TaskRunnerInterface $taskRunner): void;

    public function getIterator(): iterable;
}
