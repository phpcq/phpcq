<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Phpcq\Runner\Repository\RepositoryFactory;
use Phpcq\Runner\Resolver\RepositoryPoolResolver;
use Phpcq\Runner\Updater\Task\TaskInterface;
use Phpcq\Runner\Updater\UpdateCalculator;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateCommand extends AbstractUpdateCommand
{
    #[\Override]
    protected function configure(): void
    {
        $this->setName('update')->setDescription('Update the phpcq installation');
        $this->addOption(
            'force-reinstall',
            'f',
            InputOption::VALUE_NONE,
            'Force to reinstall existing tools'
        );

        parent::configure();
    }

    /** @psalm-return list<TaskInterface> */
    #[\Override]
    protected function calculateTasks(): array
    {
        $verbosity  = $this->input->getOption('dry-run')
            ? OutputInterface::VERBOSITY_VERBOSE
            : OutputInterface::VERBOSITY_VERY_VERBOSE;

        $factory    = new RepositoryFactory($this->repositoryLoader);
        $pool       = $factory->buildPool($this->config->getRepositories());
        $force      = $this->lockFileRepository === null || $this->input->getOption('force-reinstall');
        $calculator = new UpdateCalculator(
            $this->getInstalledRepository(false),
            new RepositoryPoolResolver($pool),
            $this->composer,
            $this->getWrappedOutput(),
            $verbosity
        );

        return $calculator->calculate($this->config->getPlugins(), $force);
    }

    /** @psalm-param list<TaskInterface> $tasks */
    #[\Override]
    protected function executeTasks(array $tasks): void
    {
        if ($this->input->getOption('dry-run')) {
            return;
        }

        parent::executeTasks($tasks);
    }
}
