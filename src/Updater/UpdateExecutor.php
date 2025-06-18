<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater;

use Phpcq\Runner\Composer;
use Phpcq\Runner\Downloader\DownloaderInterface;
use Phpcq\GnuPG\Signature\SignatureVerifier;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Repository\InstalledRepositoryDumper;
use Phpcq\Runner\Repository\LockFileDumper;
use Phpcq\Runner\Updater\Task\TaskInterface;
use Symfony\Component\Filesystem\Filesystem;

use function getcwd;

final class UpdateExecutor
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        private readonly DownloaderInterface $downloader,
        private readonly SignatureVerifier $verifier,
        private readonly string $installedPluginPath,
        private readonly OutputInterface $output,
        private readonly Composer $composer
    ) {
        $this->filesystem          = new Filesystem();
    }

    /** @param list<TaskInterface> $tasks */
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
        $dumper->dump($context->lockRepository, ((string) getcwd()) . '/.phpcq.lock');
    }
}
