<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Updater\Task\Composer;

use Phpcq\GnuPG\Signature\SignatureVerifier;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\VersionRequirement;
use Phpcq\RepositoryDefinition\VersionRequirementList;
use Phpcq\Runner\Composer;
use Phpcq\Runner\Downloader\DownloaderInterface;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Test\TemporaryFileProducingTestTrait;
use Phpcq\Runner\Updater\Task\Composer\KeepComposerDependenciesTask;
use Phpcq\Runner\Updater\UpdateContext;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Filesystem\Filesystem;

use function json_encode;

/**
 * @covers \Phpcq\Runner\Updater\Task\Composer\KeepComposerDependenciesTask
 * @covers \Phpcq\Runner\Updater\Task\Composer\AbstractComposerTask
 */
final class KeepComposerDependenciesTaskTest extends TestCase
{
    use TemporaryFileProducingTestTrait;

    public function testDescription(): void
    {
        $pluginVersion = $this->createMock(PluginVersionInterface::class);
        $pluginVersion->expects($this->once())->method('getName')->willReturn('foo');

        $instance = new KeepComposerDependenciesTask($pluginVersion);

        self::assertSame(
            'Will keep composer dependencies of plugin foo',
            $instance->getPurposeDescription()
        );
    }

    public function testExecuteStoresComposerLockAndVersionsOfExplicitlyRequiredPackages(): void
    {
        $composerLock = json_encode([
            'packages'     => [
                ['name' => 'vendor/tool', 'version' => '2.1.0'],
                ['name' => 'vendor/dependency', 'version' => '1.0.0'],
                ['name' => 'vendor/other-tool', 'version' => 'v3.0.0'],
            ],
            'packages-dev' => [
                ['name' => 'vendor/dev-tool', 'version' => '4.0.0'],
            ],
        ]);
        $filesystem   = new Filesystem();
        $filesystem->dumpFile(self::$tempdir . '/foo/composer.lock', $composerLock);

        $pluginVersion = $this->createMock(PluginVersionInterface::class);
        $pluginVersion->method('getName')->willReturn('foo');

        $installedRepository = new InstalledRepository();
        $installedRepository->addPlugin(new InstalledPlugin($pluginVersion));
        $lockRepository = new InstalledRepository();
        $lockRepository->addPlugin(new InstalledPlugin($pluginVersion));

        $requirements = new VersionRequirementList([
            new VersionRequirement('vendor/tool', '^2.0'),
            new VersionRequirement('vendor/other-tool', '^3.0'),
            new VersionRequirement('vendor/dev-tool', '^4.0'),
        ]);

        $context = new UpdateContext(
            $filesystem,
            $this->createMock(Composer::class),
            $installedRepository,
            $lockRepository,
            (new ReflectionClass(SignatureVerifier::class))->newInstanceWithoutConstructor(),
            $this->createMock(DownloaderInterface::class),
            self::$tempdir
        );

        (new KeepComposerDependenciesTask($pluginVersion, $requirements))->execute($context);

        self::assertSame($composerLock, $lockRepository->getPlugin('foo')->getComposerLock());
        self::assertSame(
            ['vendor/tool' => '2.1.0', 'vendor/other-tool' => 'v3.0.0'],
            $installedRepository->getPlugin('foo')->getComposerPackages()
        );
    }
}
