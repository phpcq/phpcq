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
        $environment = new Environment(
            $projectConfig = $this->createMock(ProjectConfigInterface::class),
            $taskFactory = $this->createMock(TaskFactoryInterface::class),
            '/temp/dir',
            10,
            '/installed-dir'
        );

        self::assertSame($projectConfig, $environment->getProjectConfiguration());
        self::assertSame($taskFactory, $environment->getTaskFactory());
        self::assertSame('/temp/dir', $environment->getBuildTempDir());
        self::assertSame(10, $environment->getAvailableThreads());
        self::assertSame('/installed-dir', $environment->getInstalledDir());
    }
}
