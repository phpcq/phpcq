<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use Generator;
use Phpcq\Runner\Exception\Exception;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Exception\RuntimeException as PluginApiRuntimeException;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\PluginApi\Version10\Task\ParallelTaskInterface;
use Phpcq\PluginApi\Version10\Task\ReportWritingTaskInterface;
use Phpcq\Runner\Report\Report;
use SplObjectStorage;

class TaskScheduler
{
    public const LOG_START  = '%1$s starting';
    public const LOG_FAILED = '%1$s failed';
    public const LOG_END    = '%1$s finished';

    /**
     * @var Generator<array-key, ReportWritingTaskInterface>
     */
    private $tasks;

    /** @var bool */
    private $stop;

    /** @var bool */
    private $success;

    /** @var int */
    private $runningThreads;

    /** @var SplObjectStorage<ParallelTaskInterface, TaskReportInterface> */
    private $threads;

    public function __construct(
        TasklistInterface $tasks,
        private readonly int $parallelThreads,
        private readonly Report $report,
        private readonly OutputInterface $output,
        private readonly bool $fastFinish
    ) {
        $this->tasks           = $this->nextTaskGenerator($tasks);
        $this->stop            = false;
        $this->success         = true;
        $this->runningThreads  = 0;
        /** @psalm-suppress PropertyTypeCoercion - the empty object storage IS the correct type. */
        $this->threads = new SplObjectStorage();
    }

    public function run(): bool
    {
        if ($this->stop) {
            throw new RuntimeException('Can not run twice.');
        }

        // Empty list.
        if (!$this->tasks->valid()) {
            $this->stop = true;
            return true;
        }
        $this->fillUp();
        while ($this->runningThreads > 0 || $this->tasks->valid()) {
            $this->tick();
            if ($this->stop) {
                break;
            }
            $this->fillUp();
            usleep(500);
        }
        // If stopped, let the remaining running tasks complete.
        while ($this->runningThreads > 0) {
            $this->tick();
            usleep(500);
        }

        $this->stop = true;

        return $this->success;
    }

    private function tick(): void
    {
        foreach ($this->threads as $thread) {
            try {
                if (false === $thread->tick()) {
                    // Plugin finished, free slot.
                    $this->removeThread($thread);
                }
            } catch (PluginApiRuntimeException $throwable) {
                $this->renderException($this->output, $throwable);
                $this->removeThread($thread);
                if ($this->fastFinish) {
                    $this->stop = true;
                    return;
                }
            }
        }
    }

    private function removeThread(ParallelTaskInterface $thread): void
    {
        $this->output->writeln(sprintf(self::LOG_END, $thread->getToolName()), OutputInterface::VERBOSITY_DEBUG);
        $report = $this->threads->offsetGet($thread);
        $this->threads->detach($thread);
        $this->runningThreads -= $thread->getCost();
        $this->success = $this->success && $report->getStatus() === TaskReportInterface::STATUS_PASSED;
        if ($this->fastFinish && !$this->success) {
            $this->stop = true;
        }
    }

    /** @SuppressWarnings(PHPMD.CyclomaticComplexity) */
    private function fillUp(): void
    {
        if ($this->stop || $this->runningThreads === $this->parallelThreads) {
            // No slot available.
            return;
        }

        if (!$this->tasks->valid()) {
            // If we have no pending tasks left.
            return;
        }

        while ($this->runningThreads < $this->parallelThreads && $this->tasks->valid()) {
            $next = $this->tasks->current();
            if ($next === null) {
                continue;
            }

            // If the pending task is not parallelizable, return if we still have some running.
            $cost = ($next instanceof ParallelTaskInterface) ? $next->getCost() : $this->parallelThreads;
            if ($this->runningThreads + $cost > $this->parallelThreads) {
                return;
            }
            $this->enqueueTask($next);

            $this->tasks->next();
            if ($this->stop) {
                break;
            }
        }
    }

    private function enqueueTask(ReportWritingTaskInterface $task): void
    {
        // Start it up.
        $name = $task->getToolName();
        $this->output->writeln(sprintf(self::LOG_START, $name), OutputInterface::VERBOSITY_DEBUG);
        $report = $this->report->addTaskReport($name);
        try {
            $task->runWithReport($report);
        } catch (PluginApiRuntimeException $throwable) {
            $this->renderException($this->output, $throwable);
            if ($this->fastFinish) {
                $this->stop = true;
            }
            return;
        }

        // We could not run this task parallel, do not attach.
        if (!$task instanceof ParallelTaskInterface) {
            $this->success = $this->success && $report->getStatus() === TaskReportInterface::STATUS_PASSED;
            if ($this->fastFinish && !$this->success) {
                $this->stop = true;
            }

            $this->output->writeln(sprintf(self::LOG_END, $name), OutputInterface::VERBOSITY_DEBUG);
            return;
        }
        $this->threads->attach($task, $report);
        $this->runningThreads += $task->getCost();
    }

    /**
     * @return Generator<int, ReportWritingTaskInterface>
     */
    private function nextTaskGenerator(TasklistInterface $tasks): Generator
    {
        foreach ($tasks->getIterator() as $task) {
            assert($task instanceof ReportWritingTaskInterface);

            yield $task;
        }
    }

    private function renderException(OutputInterface $output, PluginApiRuntimeException $exception): void
    {
        // Log internal exceptions on level normal - these indicate an error in phpcq caused from within a plugin.
        // @codeCoverageIgnoreStart
        if ($exception instanceof Exception) {
            $output->writeln('', OutputInterface::VERBOSITY_NORMAL, OutputInterface::CHANNEL_STDERR);
            $output->writeln(
                'WARNING: task execution caused internal error: "' . $exception->getMessage() . '"',
                OutputInterface::VERBOSITY_NORMAL,
                OutputInterface::CHANNEL_STDERR
            );
            $output->writeln(
                'This is most certainly caused by a plugin misbehaving.',
                OutputInterface::VERBOSITY_NORMAL,
                OutputInterface::CHANNEL_STDERR
            );
            $output->writeln(
                $exception->getFile() . ' on line ' . $exception->getLine(),
                OutputInterface::VERBOSITY_VERBOSE,
                OutputInterface::CHANNEL_STDERR
            );
            $output->writeln(
                $exception->getTraceAsString(),
                OutputInterface::VERBOSITY_VERBOSE,
                OutputInterface::CHANNEL_STDERR
            );
        }
        // @codeCoverageIgnoreEnd

        // Normal plugin exception.
        $output->writeln(
            $exception->getMessage(),
            OutputInterface::VERBOSITY_VERBOSE,
            OutputInterface::CHANNEL_STDERR
        );
    }
}
