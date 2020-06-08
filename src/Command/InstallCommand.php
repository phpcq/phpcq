<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Exception\RuntimeException;
use Phpcq\Repository\Repository;
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

    protected function doExecute(): int
    {
        if ($this->isAlreadyInstalled()) {
            $this->output->writeln('Nothing to install.');
            return 0;
        }

        return parent::doExecute();
    }

    /** @psalm-return list<TUpdateTask> */
    protected function calculateTasks(): array
    {
        if (null !== $this->lockFileRepository) {
            $this->output->writeln('Install tools from lock file.', OutputInterface::VERBOSITY_VERBOSE);

            $pool = new RepositoryPool();
            $pool->addRepository($this->lockFileRepository);
            $calculator = new UpdateCalculator(new Repository(), $pool, $this->getWrappedOutput());

            return $calculator->calculateTasksToExecute($this->lockFileRepository, $this->config['tools']);
        }

        $this->output->writeln('No lock file found. Install configured tools.', OutputInterface::VERBOSITY_VERBOSE);

        // Download repositories
        $pool       = (new RepositoryFactory($this->repositoryLoader))->buildPool($this->config['repositories'] ?? []);
        $calculator = new UpdateCalculator(new Repository(), $pool, $this->getWrappedOutput());

        return $calculator->calculate($this->config['tools'], true);
    }

    private function isAlreadyInstalled(): bool
    {
        try {
            $this->getInstalledRepository(true);
            return true;
        } catch (RuntimeException $exception) {
            return false;
        }
    }
}
