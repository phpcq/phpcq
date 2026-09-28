<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use Override;
use Phpcq\PluginApi\Version10\FixStage;
use Phpcq\PluginApi\Version10\Task\TaskInterface;
use Traversable;

use function ksort;

/**
 * Task list for fix tasks, iterated in stage order (stable within a stage).
 */
final class FixTasklist implements TasklistInterface
{
    /** @var array<int, list<TaskInterface>> */
    private array $entries = [];

    #[Override]
    public function add(TaskInterface $taskRunner): void
    {
        $this->addFixTask($taskRunner, FixStage::Format);
    }

    public function addFixTask(TaskInterface $task, FixStage $stage): void
    {
        $this->entries[$stage->order()][] = $task;
    }

    /** {@inheritDoc} */
    #[Override]
    public function getIterator(): Traversable
    {
        ksort($this->entries);

        foreach ($this->entries as $tasks) {
            foreach ($tasks as $task) {
                yield $task;
            }
        }
    }
}
