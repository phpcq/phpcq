<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Phpcq\Runner\Repository\RepositoryFactory;
use Phpcq\Runner\Resolver\LockFileRepositoryResolver;
use Phpcq\Runner\Resolver\RepositoryPoolResolver;
use Phpcq\Runner\Updater\Task\UpdateTaskInterface;
use Phpcq\Runner\Updater\UpdateCalculator;
use Symfony\Component\Console\Output\OutputInterface;

final class InstallCommand extends AbstractUpdateCommand
{
    protected function configure(): void
    {
        $this->setName('install');
        $this->setDescription('Install the phpcq installation from existing .phpcq.lock file');

        parent::configure();
    }

    /** @psalm-return list<UpdateTaskInterface> */
    protected function calculateTasks(): array
    {
        $installedRepository = $this->getInstalledRepository(false);

        if (null !== $this->lockFileRepository) {
            $this->output->writeln('Install tools from lock file.', OutputInterface::VERBOSITY_VERBOSE);

            $resolver = new LockFileRepositoryResolver($this->lockFileRepository);
            $force    = false;
        } else {
            $this->output->writeln('No lock file found. Install configured tools.', OutputInterface::VERBOSITY_VERBOSE);

            $repositories = $this->config->getRepositories();
            $pool         = (new RepositoryFactory($this->repositoryLoader))->buildPool($repositories);
            $resolver     = new RepositoryPoolResolver($pool);
            $force        = true;
        }

        $verbosity  = $this->input->getOption('dry-run')
            ? OutputInterface::VERBOSITY_VERBOSE
            : OutputInterface::VERBOSITY_VERY_VERBOSE;

        $calculator = new UpdateCalculator(
            $installedRepository,
            $resolver,
            $this->composer,
            $this->getWrappedOutput(),
            $verbosity
        );

        return $calculator->calculate($this->config->getPlugins(), $force);
    }
}
