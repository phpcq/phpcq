<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Task;

use Phpcq\PluginApi\Version10\FixStage;
use Phpcq\PluginApi\Version10\Task\TaskInterface;
use Phpcq\Runner\Task\FixTasklist;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

/** @covers \Phpcq\Runner\Task\FixTasklist */
final class FixTasklistTest extends TestCase
{
    public function testSortsByStageAndKeepsInsertionOrderWithinStage(): void
    {
        $list = new FixTasklist();
        $list->addFixTask($phpcbf = $this->createMock(TaskInterface::class), FixStage::Format);
        $list->addFixTask($rector = $this->createMock(TaskInterface::class), FixStage::Refactor);
        $list->addFixTask($other = $this->createMock(TaskInterface::class), FixStage::Format);
        $list->addFixTask($normalize = $this->createMock(TaskInterface::class), FixStage::Normalize);

        self::assertSame([$normalize, $rector, $phpcbf, $other], iterator_to_array($list->getIterator(), false));
    }

    public function testAddUsesFormatStage(): void
    {
        $list = new FixTasklist();
        $list->add($format = $this->createMock(TaskInterface::class));
        $list->addFixTask($refactor = $this->createMock(TaskInterface::class), FixStage::Refactor);

        self::assertSame([$refactor, $format], iterator_to_array($list->getIterator(), false));
    }

    public function testEmptyList(): void
    {
        self::assertSame([], iterator_to_array((new FixTasklist())->getIterator(), false));
    }
}
