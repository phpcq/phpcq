<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Generator;
use Phpcq\Exception\Exception;
use Phpcq\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\OutputInterface;
use Phpcq\PluginApi\Version10\RuntimeException as PluginApiRuntimeException;
use Phpcq\PluginApi\Version10\Task\ParallelTaskInterface;
use Phpcq\PluginApi\Version10\Task\ReportWritingTaskInterface;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Report;
use SplObjectStorage;

class TaskScheduler
{
    public const LOG_START  = '%1$s starting';
    public const LOG_FAILED = '%1$s failed';
    public const LOG_END    = '%1$s finished';

    /**
     * @var ReportWritingTaskInterface[]|Generator
     * @psalm-var Generator<array-key, ReportWritingTaskInterface>
     */
    private $tasks;

    /** @var int */
    private $parallelThreads;

    /** @var OutputInterface */
    private $output;

    /** @var Report */
    private $report;

    /** @var bool */
    private $fastFinish;

    /** @var bool */
    private $stop;

    /** @var bool */
    private $success;

    /** @var int */
    private $runningThreads;

    /** @var SplObjectStorage<ParallelTaskInterface, ToolReportInterface> */
    private $threads;

    public function __construct(
        TasklistInterface $tasks,
        int $parallelThreads,
        Report $report,
        OutputInterface $output,
        bool $fastFinish
    ) {
        $this->tasks           = $this->nextTaskGenerator($tasks);
        $this->parallelThreads = $parallelThreads;
        $this->report          = $report;
        $this->output          = $output;
        $this->fastFinish      = $fastFinish;
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
        }
        // If stopped, let the remaining running tasks complete.
        while ($this->runningThreads > 0) {
            $this->tick();
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
        $this->runningThreads--;
        $this->success = $this->success && $report->getStatus() === ToolReportInterface::STATUS_PASSED;
        if ($this->fastFinish && !$this->success) {
            $this->stop = true;
        }
    }

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
            // If the pending task is not parallelizable, return if we still have some running.
            if (! ($next instanceof ParallelTaskInterface) && $this->runningThreads > 0) {
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
        $report = $this->report->addToolReport($name);
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
            $this->success = $this->success && $report->getStatus() === ToolReportInterface::STATUS_PASSED;
            if ($this->fastFinish && !$this->success) {
                $this->stop = true;
            }

            $this->output->writeln(sprintf(self::LOG_END, $name), OutputInterface::VERBOSITY_DEBUG);
            return;
        }
        $this->threads->attach($task, $report);
        $this->runningThreads++;
    }

    /**
     * @return ReportWritingTaskInterface[]|Generator
     * @psalm-return Generator<int, ReportWritingTaskInterface>
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
