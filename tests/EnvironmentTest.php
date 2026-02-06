<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test;

use Phpcq\PluginApi\Version10\ProjectConfigInterface;
use Phpcq\PluginApi\Version10\Task\TaskFactoryInterface;
use Phpcq\Runner\Environment;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Environment */
final class EnvironmentTest extends TestCase
{
    public function testEnvironment(): void
    {
        $projectConfig = $this->createMock(ProjectConfigInterface::class);
        $taskFactory = $this->createMock(TaskFactoryInterface::class);
        $instance = new Environment($projectConfig, $taskFactory, '/tmp', 1, '/foo/bar');

        self::assertSame($projectConfig, $instance->getProjectConfiguration());
        self::assertSame($taskFactory, $instance->getTaskFactory());
        self::assertSame('/tmp', $instance->getBuildTempDir());
        self::assertSame('/foo/bar', $instance->getInstalledDir());
    }
}
