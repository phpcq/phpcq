<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Task;

use Phpcq\PluginApi\Version10\Task\TaskInterface;
use Phpcq\Runner\Task\Tasklist;
use PHPUnit\Framework\TestCase;

/**
 * Test the task list implementation.
 *
 * @covers \Phpcq\Runner\Task\Tasklist
 */
class TasklistTest extends TestCase
{
    public function testAddsAndIteratesCorrectly(): void
    {
        $list = new Tasklist();

        $list->add($task1 = $this->getMockForAbstractClass(TaskInterface::class));
        $list->add($task2 = $this->getMockForAbstractClass(TaskInterface::class));

        $tasks = [];
        foreach ($list->getIterator() as $task) {
            $this->assertInstanceOf(TaskInterface::class, $task);
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
