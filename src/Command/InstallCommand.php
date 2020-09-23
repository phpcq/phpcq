<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Runner\Repository\RepositoryFactory;
use Phpcq\Runner\Resolver\InstalledRepositoryResolver;
use Phpcq\Runner\Resolver\RepositoryPoolResolver;
use Phpcq\Runner\Updater\UpdateCalculator;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @psalm-import-type TPluginTask from \Phpcq\Runner\Updater\UpdateCalculator
 */
final class InstallCommand extends AbstractUpdateCommand
{
    protected function configure(): void
    {
        $this->setName('install');
        $this->setDescription('Install the phpcq installation from existing .phpcq.lock file');

        parent::configure();
    }

    /** @psalm-return list<TPluginTask> */
    protected function calculateTasks(): array
    {
        $installedRepository = $this->getInstalledRepository(false);

        if (null !== $this->lockFileRepository) {
            $this->output->writeln('Install tools from lock file.', OutputInterface::VERBOSITY_VERBOSE);

            $resolver = new InstalledRepositoryResolver($this->lockFileRepository);
            $force    = false;
        } else {
            $this->output->writeln('No lock file found. Install configured tools.', OutputInterface::VERBOSITY_VERBOSE);

            $repositories = $this->config->getRepositories();
            $pool         = (new RepositoryFactory($this->repositoryLoader))->buildPool($repositories);
            $resolver     = new RepositoryPoolResolver($pool);
            $force        = true;
        }

        $calculator = new UpdateCalculator($installedRepository, $resolver, $this->getWrappedOutput());

        return $calculator->calculate($this->config->getTools(), $force);
    }
}
