<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Phpcq\Exception\RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

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
