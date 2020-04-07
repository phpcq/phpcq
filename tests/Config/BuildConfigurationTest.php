<?php

declare(strict_types=1);

namespace Phpcq\Test\Config;

use Phpcq\Config\BuildConfiguration;
use Phpcq\PluginApi\Version10\ProjectConfigInterface;
use Phpcq\PluginApi\Version10\TaskFactoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Config\BuildConfiguration
 */
final class BuildConfigurationTest extends TestCase
{
    public function testCreate(): void
    {
        $configuration = new BuildConfiguration(
            $projectConfig = $this->getMockForAbstractClass(ProjectConfigInterface::class),
            $taskFactory = $this->getMockForAbstractClass(TaskFactoryInterface::class),
            '/temp/dir'
        );

        self::assertSame($projectConfig, $configuration->getProjectConfiguration());
        self::assertSame($taskFactory, $configuration->getTaskFactory());
        self::assertSame('/temp/dir', $configuration->getBuildTempDir());
    }
}
