<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Phpcq\PluginApi\Version10\ConfigurationPluginInterface;
use Phpcq\PluginApi\Version10\PluginInterface;
use Phpcq\Runner\Config\PluginConfiguration;
use Phpcq\Runner\Config\PluginConfigurationFactory;
use Phpcq\Runner\Config\ProjectConfiguration;
use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Plugin\ChainPlugin;
use Phpcq\Runner\Plugin\PluginRegistry;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\Runner\Report\Report;
use Phpcq\Runner\Report\Writer\CheckstyleReportWriter;
use Phpcq\Runner\Environment;
use Phpcq\PluginApi\Version10\DiagnosticsPluginInterface;
use Phpcq\Runner\Report\Buffer\ReportBuffer;
use Phpcq\Runner\Report\Writer\CodeClimateReportWriter;
use Phpcq\Runner\Report\Writer\ConsoleWriter;
use Phpcq\Runner\Report\Writer\FileReportWriter;
use Phpcq\Runner\Report\Writer\GithubActionConsoleWriter;
use Phpcq\Runner\Report\Writer\ReportWriterInterface;
use Phpcq\Runner\Report\Writer\TaskReportWriter;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Task\ResolvedTask;
use Phpcq\Runner\Task\TaskFactory;
use Phpcq\Runner\Task\Tasklist;
use Phpcq\Runner\Task\TaskScheduler;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Generator;
use Throwable;

use function array_keys;
use function assert;
use function dirname;
use function getcwd;
use function is_string;
use function min;
use function sort;

/**
 * Base class of the commands executing the configured tasks.
 */
abstract class AbstractTaskCommand extends AbstractCommand
{
    use InstalledRepositoryLoadingCommandTrait;

    /** @var array<string, class-string<ReportWriterInterface>> */
    private const REPORT_FORMATS = [
        'task-report'  => TaskReportWriter::class,
        'file-report'  => FileReportWriter::class,
        'checkstyle'   => CheckstyleReportWriter::class,
        'code-climate' => CodeClimateReportWriter::class,
    ];

    /**
     * Only valid when examined from within doExecute().
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private PluginConfigurationFactory $pluginConfigFactory;

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument(
            'task',
            InputArgument::OPTIONAL,
            'Define a specific task which should be run',
            'default'
        );
        $this->addOption(
            'fast-finish',
            'ff',
            InputOption::VALUE_NONE,
            'Do not keep going and execute all tasks but break on first error',
        );

        $this->addOption(
            'exit-0',
            '0',
            InputOption::VALUE_NONE,
            'Forces the exit code to 0 - this is useful to "ignore" failures in CI as "allow-failure" mode',
        );

        $this->addOption(
            'report',
            'r',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Set the report formats which should be created. Available options are <info>file-report</info>, '
            . '<info>task-report</info> and <info>checkstyle</info>".',
            ['file-report']
        );

        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Set a specific console output format. Available options are <info>default</info> and '
            . '<info>github-action</info>',
            ['default']
        );

        $this->addOption(
            'threshold',
            null,
            InputOption::VALUE_REQUIRED,
            'Set the minimum threshold for diagnostics to be reported, Available options are (in ascending order): "' .
            implode('", "', [
                TaskReportInterface::SEVERITY_NONE,
                TaskReportInterface::SEVERITY_INFO,
                TaskReportInterface::SEVERITY_MINOR,
                TaskReportInterface::SEVERITY_MARGINAL,
                TaskReportInterface::SEVERITY_MAJOR,
                TaskReportInterface::SEVERITY_FATAL,
            ]) . '"',
            TaskReportInterface::SEVERITY_MARGINAL
        );

        $numCores = $this->getCores();
        $this->addOption(
            'threads',
            'j',
            InputOption::VALUE_REQUIRED,
            sprintf('Set the amount of threads to run in parallel. <info>1</info>-<info>%1$d</info>', $numCores),
            $numCores
        );

        parent::configure();
    }

    #[\Override]
    protected function doExecute(): int
    {
        // Stage 1: preparation.
        $maxCores      = min($this->getCores(), (int) $this->input->getOption('threads'));
        $projectConfig = $this->createProjectConfiguration($maxCores);
        $tempDirectory = $this->createTempDirectory();
        $fileSystem    = new Filesystem();
        $installed     = $this->getInstalledRepository(true);
        $outputPath    = $projectConfig->getArtifactOutputPath();

        $fileSystem->remove($outputPath);
        $fileSystem->mkdir($outputPath);

        $plugins = PluginRegistry::buildFromInstalledRepository($installed);
        $taskList = new Tasklist();
        $taskName = $this->input->getArgument('task') ?: 'default';
        assert(is_string($taskName));

        $this->pluginConfigFactory = new PluginConfigurationFactory($this->config, $plugins, $installed);

        $resolvedTasks = $this->resolveTasks(
            $plugins,
            $installed,
            $taskName,
            $projectConfig,
            $tempDirectory,
            $maxCores
        );
        foreach ($resolvedTasks as $resolvedTask) {
            $this->handleTask($resolvedTask, $taskList);
        }

        // Stage 2: execution.
        $reportBuffer  = new ReportBuffer();
        $report        = new Report($reportBuffer, $tempDirectory);
        $consoleOutput = $this->getWrappedOutput();
        $exitCode      = $this->executeTasks($taskList, $report, $consoleOutput) ? 0 : 1;

        // Stage 3: reporting.
        $reportBuffer->complete($exitCode === 0 ? Report::STATUS_PASSED : Report::STATUS_FAILED);
        $this->writeReports($reportBuffer, $projectConfig);

        // Stage 4. cleanup.
        $consoleOutput->writeln('Finished.', OutputInterface::VERBOSITY_VERBOSE, OutputInterface::CHANNEL_STDERR);
        $fileSystem->remove($tempDirectory);

        if ($this->input->getOption('exit-0')) {
            return 0;
        }

        return  $exitCode;
    }

    #[\Override]
    protected function doComplete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('task')) {
            $tasks = array_keys($this->config->getTaskConfig());
            sort($tasks);
            $suggestions->suggestValues($tasks);
        }

        if ($input->mustSuggestOptionValuesFor('output')) {
            $suggestions->suggestValues(['github-action', 'default']);
        }

        if ($input->mustSuggestOptionValuesFor('report')) {
            $reports = array_keys(self::REPORT_FORMATS);
            sort($reports);
            $suggestions->suggestValues($reports);
        }

        if ($input->mustSuggestOptionValuesFor('threshold')) {
            $suggestions->suggestValues(
                [
                    TaskReportInterface::SEVERITY_NONE,
                    TaskReportInterface::SEVERITY_INFO,
                    TaskReportInterface::SEVERITY_MINOR,
                    TaskReportInterface::SEVERITY_MARGINAL,
                    TaskReportInterface::SEVERITY_MAJOR,
                    TaskReportInterface::SEVERITY_FATAL
                ]
            );
        }
    }

    /**
     * Execute the collected tasks.
     *
     * @return bool True if all tasks passed.
     */
    protected function executeTasks(Tasklist $taskList, Report $report, OutputInterface $output): bool
    {
        $fastFinish = (bool) $this->input->getOption('fast-finish');
        $threads    = (int) $this->input->getOption('threads');
        $scheduler  = new TaskScheduler($taskList, $threads, $report, $output, $fastFinish);

        return $scheduler->run();
    }

    /**
     * Add the tasks of a resolved task to the task list.
     */
    protected function handleTask(ResolvedTask $resolvedTask, Tasklist $taskList): void
    {
        $plugin = $resolvedTask->plugin;
        if (!$plugin instanceof DiagnosticsPluginInterface) {
            return;
        }

        foreach ($plugin->createDiagnosticTasks($resolvedTask->configuration, $resolvedTask->environment) as $task) {
            $taskList->add($task);
        }
    }

    /**
     * Resolve the task and the children of chain plugins to the tasks providing a configuration.
     *
     * @return Generator<int, ResolvedTask>
     */
    private function resolveTasks(
        PluginRegistry $plugins,
        InstalledRepository $installed,
        string $taskName,
        ProjectConfiguration $projectConfig,
        string $tempDirectory,
        int $availableThreads
    ): Generator {
        $configValues = $this->config->getConfigForTask($taskName);
        $plugin       = $plugins->getPluginByName($configValues['plugin'] ?? $taskName);
        $environment  = $this->createEnvironment(
            $plugin,
            $installed,
            $taskName,
            $projectConfig,
            $tempDirectory,
            $availableThreads
        );
        $configuration = $this->createConfiguration($plugin, $taskName, $environment);

        if ($plugin instanceof ChainPlugin) {
            assert($configuration instanceof PluginConfiguration);

            foreach ($plugin->getTaskNames($configuration) as $childTask) {
                yield from $this->resolveTasks(
                    $plugins,
                    $installed,
                    $childTask,
                    $projectConfig,
                    $tempDirectory,
                    $availableThreads
                );
            }

            return;
        }

        // Only plugins implementing the ConfigurationPluginInterface provide tasks.
        if (null === $configuration) {
            return;
        }

        yield new ResolvedTask($taskName, $configValues, $plugin, $configuration, $environment);
    }

    private function createConfiguration(
        PluginInterface $plugin,
        string $taskName,
        Environment $environment
    ): ?PluginConfiguration {
        if (!$plugin instanceof ConfigurationPluginInterface) {
            return null;
        }

        try {
            return $this->pluginConfigFactory->createForTask($taskName, $environment);
        } catch (ConfigurationValidationErrorException $exception) {
            throw $exception->withOuterPath(['tasks', $taskName]);
        } catch (Throwable $exception) {
            throw ConfigurationValidationErrorException::fromError(['tasks', $taskName, 'config'], $exception);
        }
    }

    private function createEnvironment(
        PluginInterface $plugin,
        InstalledRepository $installed,
        string $taskName,
        ProjectConfiguration $projectConfig,
        string $tempDirectory,
        int $availableThreads
    ): Environment {
        $installedPlugin = $installed->getPlugin($plugin->getName());
        [$phpCli, $phpArguments] = $this->findPhpCli();

        return new Environment(
            $projectConfig,
            new TaskFactory($taskName, $installedPlugin, $phpCli, $phpArguments),
            $tempDirectory,
            $availableThreads,
            dirname($installedPlugin->getPluginVersion()->getFilePath())
        );
    }

    private function writeReports(ReportBuffer $report, ProjectConfiguration $projectConfig): void
    {
        $threshold = (string) $this->input->getOption('threshold');
        /** @var list<string> $formats */
        $formats   = (array) $this->input->getOption('output');
        if ([] !== ($unsupported = array_diff($formats, ['github-action', 'default']))) {
            throw new RuntimeException(sprintf('Output formats "%s" are not supported', implode(', ', $unsupported)));
        }

        if (in_array('github-action', $formats, true)) {
            GithubActionConsoleWriter::writeReport($this->output, $report);
        }

        if (in_array('default', $formats, true)) {
            ConsoleWriter::writeReport(
                $this->output,
                new SymfonyStyle($this->input, $this->output),
                $report,
                $threshold,
                $this->getWrapWidth()
            );
        }

        /** @var list<string> $reports */
        $reports = (array) $this->input->getOption('report');
        $targetPath = ((string) getcwd()) . '/' . $projectConfig->getArtifactOutputPath();

        foreach ($reports as $format) {
            if (!isset(self::REPORT_FORMATS[$format])) {
                throw new RuntimeException(sprintf('Report format "%s" is not supported', $format));
            }
            $writer = self::REPORT_FORMATS[$format];
            $writer::writeReport($targetPath, $report, $threshold);
        }

        // Clean up attachments.
        $fileSystem = new Filesystem();
        foreach ($report->getTaskReports() as $taskReport) {
            foreach ($taskReport->getAttachments() as $attachment) {
                $fileSystem->remove($attachment->getAbsolutePath());
            }
        }
    }

    private function getCores(): int
    {
        if ('/' === DIRECTORY_SEPARATOR) {
            $process = new Process(['nproc']);
            try {
                $process->mustRun();
                return (int) trim($process->getOutput());
            } catch (Throwable) {
                // Fallback to grep.
                $process = new Process(['grep', '-c', '^processor', '/proc/cpuinfo']);
                try {
                    $process->mustRun();
                    return (int) trim($process->getOutput());
                } catch (Throwable) {
                    // Ignore exception and return the 1 default below.
                }
            }
        }
        // Unsupported OS.
        return 1;
    }
}
