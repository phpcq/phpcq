<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config;

use Phpcq\Runner\Config\ProjectConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Config\ProjectConfiguration
 */
final class ProjectConfigurationTest extends TestCase
{
    public function testCreate(): void
    {
        $configuration = new ProjectConfiguration(
            '/some/path',
            ['dir1', 'dir2'],
            '/output/path',
            5
        );

        self::assertSame('/some/path', $configuration->getProjectRootPath());
        self::assertSame(['dir1', 'dir2'], $configuration->getDirectories());
        self::assertSame('/output/path', $configuration->getArtifactOutputPath());
        self::assertSame(5, $configuration->getMaxCpuCores());
    }
}
