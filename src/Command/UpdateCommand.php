<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Phpcq\Runner\Repository\RepositoryFactory;
use Phpcq\Runner\Resolver\RepositoryPoolResolver;
use Phpcq\Runner\Updater\UpdateCalculator;
use Symfony\Component\Console\Input\InputOption;

/**
 * @psalm-import-type TPluginTask from \Phpcq\Runner\Updater\UpdateCalculator
 */
final class UpdateCommand extends AbstractUpdateCommand
{
    protected function configure(): void
    {
        $this->setName('update')->setDescription('Update the phpcq installation');
        $this->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Dry run'
        );
        $this->addOption(
            'force-reinstall',
            'f',
            InputOption::VALUE_NONE,
            'Force to reinstall existing tools'
        );

        parent::configure();
    }

    /** @psalm-return list<TPluginTask> */
    protected function calculateTasks(): array
    {
        $factory    = new RepositoryFactory($this->repositoryLoader);
        $pool       = $factory->buildPool($this->config->getRepositories());
        $force      = $this->lockFileRepository === null || $this->input->getOption('force-reinstall');
        $calculator = new UpdateCalculator(
            $this->getInstalledRepository(false),
            new RepositoryPoolResolver($pool),
            $this->getWrappedOutput()
        );

        return $calculator->calculate($this->config->getPlugins(), $force);
    }

    /** @psalm-param list<TPluginTask> $tasks */
    protected function executeTasks(array $tasks): void
    {
        if ($this->input->getOption('dry-run')) {
            foreach ($tasks as $task) {
                $this->output->writeln($task['message']);
            }
            return;
        }

        parent::executeTasks($tasks);
    }
}
