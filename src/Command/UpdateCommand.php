<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Repository\RepositoryFactory;
use Phpcq\ToolUpdate\UpdateCalculator;
use Symfony\Component\Console\Input\InputOption;

/**
 * @psalm-import-type TUpdateTask from \Phpcq\ToolUpdate\UpdateCalculator
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

    protected function calculateTasks(): array
    {
        $factory  = new RepositoryFactory($this->repositoryLoader);
        // Download repositories
        $pool = $factory->buildPool($this->config['repositories'] ?? []);
        $calculator = new UpdateCalculator($this->getInstalledRepository(false), $pool, $this->getWrappedOutput());
        $force = $this->lockFileRepository === null || $this->input->getOption('force-reinstall');

        return $calculator->calculate($this->config['tools'], $force);
    }

    /** @psalm-param list<TUpdateTask> $tasks */
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
