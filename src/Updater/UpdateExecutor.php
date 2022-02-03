<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater;

use Phpcq\Runner\Updater\Composer\ComposerRunner;
use Phpcq\Runner\Downloader\DownloaderInterface;
use Phpcq\GnuPG\Signature\SignatureVerifier;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Repository\InstalledRepositoryDumper;
use Phpcq\Runner\Repository\LockFileDumper;
use Phpcq\Runner\Updater\Task\UpdateTaskInterface;
use Symfony\Component\Filesystem\Filesystem;

use function getcwd;

final class UpdateExecutor
{
    /**
     * @var DownloaderInterface
     */
    private $downloader;

    /**
     * @var string
     */
    private $installedPluginPath;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var SignatureVerifier
     */
    private $verifier;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ComposerRunner
     */
    private $composer;

    public function __construct(
        DownloaderInterface $downloader,
        SignatureVerifier $verifier,
        string $pluginPath,
        OutputInterface $output,
        ComposerRunner $composer
    ) {
        $this->downloader          = $downloader;
        $this->verifier            = $verifier;
        $this->installedPluginPath = $pluginPath;
        $this->output              = $output;
        $this->filesystem          = new Filesystem();
        $this->composer            = $composer;
    }

    /** @psalm-param list<UpdateTaskInterface> $tasks */
    public function execute(array $tasks): void
    {
        $context = new UpdateContext(
            $this->filesystem,
            $this->composer,
            new InstalledRepository(),
            new InstalledRepository(),
            $this->verifier,
            $this->downloader,
            $this->installedPluginPath
        );

        foreach ($tasks as $task) {
            $this->output->writeln($task->getExecutionDescription(), OutputInterface::VERBOSITY_VERBOSE);
            $task->execute($context);
        }

        // Save installed repository.
        $filesystem = new Filesystem();
        $dumper = new InstalledRepositoryDumper($filesystem);
        $dumper->dump($context->installedRepository, $this->installedPluginPath . '/installed.json');

        $dumper = new LockFileDumper($filesystem);
        $dumper->dump($context->lockRepository, getcwd() . '/.phpcq.lock');
    }
}
