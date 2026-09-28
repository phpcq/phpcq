<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Phpcq\PluginApi\Version10\FixPluginInterface;
use Phpcq\PluginApi\Version10\FixStage;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\Runner\Report\Report;
use Phpcq\Runner\Report\TaskKind;
use Phpcq\Runner\Task\FixTasklist;
use Phpcq\Runner\Task\ResolvedTask;
use Phpcq\Runner\Task\SequentialTaskRunner;
use Phpcq\Runner\Task\Tasklist;

/**
 * Runs the fix tasks before the diagnostics.
 *
 * The fix tasks are executed sequentially. With fast finish enabled, the diagnostics are skipped when a fix task
 * failed.
 */
final class FixCommand extends AbstractTaskCommand
{
    /**
     * Only valid when examined from within doExecute().
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private FixTasklist $fixTasks;

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setName('fix')
            ->setDescription('Run the fixers of the configured tasks and report the remaining diagnostics');

        parent::configure();
    }

    #[\Override]
    protected function doExecute(): int
    {
        $this->fixTasks = new FixTasklist();

        return parent::doExecute();
    }

    #[\Override]
    protected function handleTask(ResolvedTask $resolvedTask, Tasklist $taskList): void
    {
        parent::handleTask($resolvedTask, $taskList);

        $plugin = $resolvedTask->plugin;
        if (!$plugin instanceof FixPluginInterface) {
            return;
        }

        // The fix stage override is validated while loading the configuration.
        $override = $resolvedTask->taskConfig['fix-stage'] ?? null;
        $stage    = null === $override ? $plugin->getFixStage() : FixStage::from($override);

        foreach ($plugin->createFixTasks($resolvedTask->configuration, $resolvedTask->environment) as $task) {
            $this->fixTasks->addFixTask($task, $stage);
        }
    }

    #[\Override]
    protected function executeTasks(Tasklist $taskList, Report $report, OutputInterface $output): bool
    {
        $fastFinish = (bool) $this->input->getOption('fast-finish');
        $runner     = new SequentialTaskRunner($report->withKind(TaskKind::Fix), $output, $fastFinish);
        $success    = $runner->run($this->fixTasks);
        if (!$success && $fastFinish) {
            return false;
        }

        return parent::executeTasks($taskList, $report, $output) && $success;
    }
}
