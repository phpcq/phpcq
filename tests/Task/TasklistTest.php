<?php

declare(strict_types=1);

namespace Phpcq\Test\Task;

use Phpcq\PluginApi\Version10\TaskRunnerInterface;
use Phpcq\Task\Tasklist;
use PHPUnit\Framework\TestCase;

/**
 * Test the task list implementation.
 *
 * @covers \Phpcq\Task\Tasklist
 */
class TasklistTest extends TestCase
{
    public function testAddsAndIteratesCorrectly(): void
    {
        $list = new Tasklist();

        $list->add($task1 = $this->getMockForAbstractClass(TaskRunnerInterface::class));
        $list->add($task2 = $this->getMockForAbstractClass(TaskRunnerInterface::class));

        $tasks = [];
        foreach ($list->getIterator() as $task) {
            $this->assertInstanceOf(TaskRunnerInterface::class, $task);
            $tasks[] = $task;
        }

        $this->assertSame([$task1, $task2], $tasks);
    }

    public function testIteratesEmptyCorrectly(): void
    {
        $list = new Tasklist();
        iterator_to_array($list->getIterator());
        $this->addToAssertionCount(1);
    }
}
