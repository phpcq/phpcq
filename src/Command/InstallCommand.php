<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Repository\RepositoryFactory;
use Phpcq\Repository\RepositoryPool;
use Phpcq\ToolUpdate\UpdateCalculator;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @psalm-import-type TUpdateTask from \Phpcq\ToolUpdate\UpdateCalculator
 */
final class InstallCommand extends AbstractUpdateCommand
{
    protected function configure(): void
    {
        $this->setName('install');
        $this->setDescription('Install the phpcq installation from existing .phpcq.lock file');

        parent::configure();
    }

    /** @psalm-return list<TUpdateTask> */
    protected function calculateTasks(): array
    {
        $installedRepository = $this->getInstalledRepository(false);

        if (null !== $this->lockFileRepository) {
            $this->output->writeln('Install tools from lock file.', OutputInterface::VERBOSITY_VERBOSE);

            $pool = new RepositoryPool();
            $pool->addRepository($this->lockFileRepository);
            $calculator = new UpdateCalculator($installedRepository, $pool, $this->getWrappedOutput());

            return $calculator->calculateTasksToExecute($this->lockFileRepository, $this->config->getTools());
        }

        $this->output->writeln('No lock file found. Install configured tools.', OutputInterface::VERBOSITY_VERBOSE);

        // Download repositories
        $repositories = $this->config->getRepositories();
        $pool         = (new RepositoryFactory($this->repositoryLoader))->buildPool($repositories);
        $calculator   = new UpdateCalculator($installedRepository, $pool, $this->getWrappedOutput());

        return $calculator->calculate($this->config->getTools(), true);
    }
}
