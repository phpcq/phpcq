<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use Phpcq\PluginApi\Version10\Exception\RuntimeException as PluginApiRuntimeException;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\PluginApi\Version10\Task\ParallelTaskInterface;
use Phpcq\PluginApi\Version10\Task\ReportWritingTaskInterface;

use function assert;
use function sprintf;
use function usleep;

/**
 * Runs tasks strictly one after another, ignoring their costs.
 */
final class SequentialTaskRunner
{
    public function __construct(
        private readonly ReportInterface $report,
        private readonly OutputInterface $output,
        private readonly bool $fastFinish
    ) {
    }

    public function run(TasklistInterface $tasks): bool
    {
        $success = true;
        foreach ($tasks->getIterator() as $task) {
            assert($task instanceof ReportWritingTaskInterface);
            $success = $this->runTask($task) && $success;
            if (!$success && $this->fastFinish) {
                return false;
            }
        }

        return $success;
    }

    private function runTask(ReportWritingTaskInterface $task): bool
    {
        $name = $task->getToolName();
        $this->output->writeln(sprintf(TaskScheduler::LOG_START, $name), OutputInterface::VERBOSITY_DEBUG);
        $report = $this->report->addTaskReport($name);

        try {
            $task->runWithReport($report);
            if ($task instanceof ParallelTaskInterface) {
                while ($task->tick()) {
                    usleep(500);
                }
            }
        } catch (PluginApiRuntimeException $exception) {
            $this->output->writeln(
                sprintf(TaskScheduler::LOG_FAILED, $name) . ': ' . $exception->getMessage(),
                OutputInterface::VERBOSITY_VERBOSE,
                OutputInterface::CHANNEL_STDERR
            );
            if (TaskReportInterface::STATUS_STARTED === $report->getStatus()) {
                $report
                    ->addDiagnostic(
                        TaskReportInterface::SEVERITY_FATAL,
                        sprintf('Task "%s" failed: %s', $name, $exception->getMessage())
                    )
                    ->end();
                $report->close(TaskReportInterface::STATUS_FAILED);
            }

            return false;
        }

        $this->output->writeln(sprintf(TaskScheduler::LOG_END, $name), OutputInterface::VERBOSITY_DEBUG);

        return $report->getStatus() === TaskReportInterface::STATUS_PASSED;
    }
}
