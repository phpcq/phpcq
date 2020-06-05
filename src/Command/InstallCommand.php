<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Exception\RuntimeException;
use Phpcq\FileDownloader;
use Phpcq\GnuPG\Downloader\KeyDownloader;
use Phpcq\GnuPG\GnuPGFactory;
use Phpcq\GnuPG\Signature\SignatureVerifier;
use Phpcq\Platform\PlatformRequirementChecker;
use Phpcq\Repository\JsonRepositoryLoader;
use Phpcq\Repository\RemoteRepository;
use Phpcq\Repository\Repository;
use Phpcq\Repository\RepositoryPool;
use Phpcq\Signature\SignatureFileDownloader;
use Phpcq\ToolUpdate\UpdateCalculator;
use Phpcq\ToolUpdate\UpdateExecutor;
use Symfony\Component\Console\Input\InputOption;

final class InstallCommand extends AbstractCommand
{
    use InstalledRepositoryLoadingCommandTrait;
    use LockFileRepositoryTrait;
    use UntrustedKeyStrategyTrait;

    protected function configure(): void
    {
        $this->setName('install');
        $this->setDescription('Install the phpcq installation from existing .phpcq.lock file');
        $this->addOption(
            'cache',
            'x',
            InputOption::VALUE_REQUIRED,
            'Path to the phpcq cache directory',
            (getenv('HOME') ?: sys_get_temp_dir()) . '/.cache/phpcq'
        );

        $this->addOption(
            'trust-keys',
            'k',
            InputOption::VALUE_NONE,
            'Add all keys to trusted key storage'
        );

        parent::configure();
    }

    protected function doExecute(): int
    {
        $cachePath = $this->input->getOption('cache');
        assert(is_string($cachePath));
        $this->createDirectory($cachePath);

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln('Using CACHE: ' . $cachePath);
        }

        $requirementChecker = !$this->input->getOption('ignore-platform-reqs')
            ? PlatformRequirementChecker::create()
            : PlatformRequirementChecker::createAlwaysFulfilling();

        $downloader       = new FileDownloader($cachePath, $this->config['auth'] ?? []);
        $repositoryLoader = new JsonRepositoryLoader($requirementChecker, $downloader, true);
        $consoleOutput    = $this->getWrappedOutput();

        // Check if lockfile exists.
        $lockRepository = $this->loadLockFileRepository($repositoryLoader);
        if (null === $lockRepository) {
            throw new RuntimeException('No .phpcq.lock file found in current working directory.');
        }

        // Check if installed.json exists
        try {
            $this->getInstalledRepository(true);
            $alreadyInstalled = true;
        } catch (RuntimeException $exception) {
            $alreadyInstalled = false;
        }

        if ($alreadyInstalled) {
            // Fixme: Auto run an update
            throw new RuntimeException('PHPCQ seems already being installed. You might want to perform an update');
        }

        $pool = new RepositoryPool();
        $pool->addRepository($lockRepository);

        $calculator = new UpdateCalculator(new Repository(), $pool, $consoleOutput);
        $tasks      = $calculator->calculateTasksToExecute($lockRepository, $this->config['tools']);

        $signatureVerifier = new SignatureVerifier(
            (new GnuPGFactory(sys_get_temp_dir()))->create($this->phpcqPath),
            new KeyDownloader(new SignatureFileDownloader($downloader)),
            $this->getUntrustedKeyStrategy()
        );

        $executor = new UpdateExecutor(
            $downloader,
            $signatureVerifier,
            $this->phpcqPath,
            $consoleOutput,
            $lockRepository
        );
        $executor->execute($tasks);

        return 0;
    }
}
