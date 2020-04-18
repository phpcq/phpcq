<?php

declare(strict_types=1);

namespace Phpcq\Test\Repository;

use Phpcq\Exception\RuntimeException;
use Phpcq\Platform\PlatformRequirementChecker;
use Phpcq\Repository\InstalledBootstrap;
use Phpcq\Repository\InstalledRepositoryLoader;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Repository\InstalledRepositoryLoader
 */
class InstalledRepositoryLoaderTest extends TestCase
{
    public function testLoading(): void
    {
        $instance = new InstalledRepositoryLoader(PlatformRequirementChecker::create());
        $repository = $instance->loadFile(__DIR__ . '/../fixtures/repositories/installed-repository/installed.json');

        $this->assertTrue($repository->hasTool('phar-1', '^1.0.0'));
        $tool1 = $repository->getTool('phar-1', '^1.0.0');
        $this->assertInstanceOf(InstalledBootstrap::class, $tool1->getBootstrap());
    }

    public function testLoadingFromRelativePath(): void
    {
        $instance = new InstalledRepositoryLoader(PlatformRequirementChecker::create());
        $repository = $instance->loadFile('installed.json', __DIR__ . '/../fixtures/repositories/installed-repository');

        $this->assertTrue($repository->hasTool('phar-1', '^1.0.0'));
        $tool1 = $repository->getTool('phar-1', '^1.0.0');
        $this->assertInstanceOf(InstalledBootstrap::class, $tool1->getBootstrap());
    }

    public function testLoadingForNonExistingRelativePath(): void
    {
        $instance = new InstalledRepositoryLoader(PlatformRequirementChecker::create());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File not found: ./installed.json');

        $instance->loadFile('installed.json');
    }

    public function testLoadingForNonExistingRelativePathWithBaseDir(): void
    {
        $instance = new InstalledRepositoryLoader(PlatformRequirementChecker::create());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File not found: /does/not/exist/installed.json');

        $instance->loadFile('installed.json', '/does/not/exist');
    }

    public function testLoadingForNonExistingAbsolutePath(): void
    {
        $instance = new InstalledRepositoryLoader(PlatformRequirementChecker::create());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File not found: /does/not/exist/installed.json');

        $instance->loadFile('/does/not/exist/installed.json');
    }
}