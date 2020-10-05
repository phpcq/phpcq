<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config;

use Phpcq\Runner\Environment;
use Phpcq\PluginApi\Version10\ProjectConfigInterface;
use Phpcq\PluginApi\Version10\Task\TaskFactoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Environment
 */
final class EnvironmentTest extends TestCase
{
    public function testCreate(): void
    {
        $configuration = new Environment(
            $projectConfig = $this->getMockForAbstractClass(ProjectConfigInterface::class),
            $taskFactory = $this->getMockForAbstractClass(TaskFactoryInterface::class),
            '/temp/dir'
        );

        self::assertSame($projectConfig, $configuration->getProjectConfiguration());
        self::assertSame($taskFactory, $configuration->getTaskFactory());
        self::assertSame('/temp/dir', $configuration->getBuildTempDir());
    }
}
