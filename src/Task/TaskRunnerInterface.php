<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Phpcq\Exception\RuntimeException;
use Phpcq\Output\OutputInterface;

interface TaskRunnerInterface
{
    /**
     * Run the task.
     *
     * @param OutputInterface $output
     *
     * @throws RuntimeException When task fails.
     */
    public function run(OutputInterface $output): void;
}
