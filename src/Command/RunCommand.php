<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Phpcq\Runner\Config\Builder\PluginConfigurationBuilder;
use Phpcq\Runner\Config\PluginConfiguration;
use Phpcq\Runner\Config\ProjectConfiguration;
use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Plugin\PluginRegistry;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\Runner\Report\Report;
use Phpcq\Runner\Report\Writer\CheckstyleReportWriter;
use Phpcq\Runner\Environment;
use Phpcq\PluginApi\Version10\DiagnosticsPluginInterface;
use Phpcq\Runner\Report\Buffer\ReportBuffer;
use Phpcq\Runner\Report\Writer\ConsoleWriter;
use Phpcq\Runner\Report\Writer\FileReportWriter;
use Phpcq\Runner\Report\Writer\GithubActionConsoleWriter;
use Phpcq\Runner\Report\Writer\TaskReportWriter;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Task\TaskFactory;
use Phpcq\Runner\Task\Tasklist;
use Phpcq\Runner\Task\TaskScheduler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

use function assert;
use function getcwd;
use function is_string;
use function min;

final class RunCommand extends AbstractCommand
{
    use InstalledRepositoryLoadingCommandTrait;

    /** @var array<string, class-string<\Phpcq\Runner\Report\Writer\AbstractReportWriter>> */
    private const REPORT_FORMATS = [
        'task-report' => TaskReportWriter::class,
        'file-report' => FileReportWriter::class,
        'checkstyle'  => CheckstyleReportWriter::class,
    ];

    protected function configure(): void
    {
        $this->setName('run')->setDescription('Run configured build tasks');

        $this->addArgument(
            'chain',
            InputArgument::OPTIONAL,
            'Define the tool chain. Using default chain if none passed',
            'default'
        );

        $this->addArgument(
            'task',
            InputArgument::OPTIONAL,
            'Define a specific task which should be run'
        );
        $this->addOption(
            'fast-finish',
            'ff',
            InputOption::VALUE_NONE,
            'Do not keep going and execute all tasks but break on first error',
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

    protected function doExecute(): int
    {
        // Stage 1: preparation.
        $maxCores      = min($this->getCores(), (int)$this->input->getOption('threads'));
        $artifactDir   = $this->config->getArtifactDir();
        $fileSystem    = new Filesystem();
        $projectConfig = new ProjectConfiguration(getcwd(), $this->config->getDirectories(), $artifactDir, $maxCores);

        $tempDirectory = sys_get_temp_dir() . '/' . uniqid('phpcq-');
        $fileSystem->mkdir($tempDirectory);

        $installed = $this->getInstalledRepository(true);
        $chain = $this->input->getArgument('chain');
        assert(is_string($chain));

        $chains = $this->config->getChains();
        if (!isset($chains[$chain])) {
            throw new RuntimeException(sprintf('Unknown chain "%s"', $chain));
        }

        $outputPath = $projectConfig->getArtifactOutputPath();
        $fileSystem->remove($outputPath);
        $fileSystem->mkdir($outputPath);

        $plugins = PluginRegistry::buildFromInstalledRepository($installed);
        $taskList = new Tasklist();
        if ($taskName = $this->input->getArgument('task')) {
            assert(is_string($taskName));
            $this->handleTask($plugins, $installed, $taskName, $projectConfig, $tempDirectory, $taskList, $maxCores);
        } else {
            foreach ($chains[$chain] as $taskName) {
                $this->handleTask(
                    $plugins,
                    $installed,
                    $taskName,
                    $projectConfig,
                    $tempDirectory,
                    $taskList,
                    $maxCores
                );
            }
        }

        // Stage 2: execution.
        $reportBuffer  = new ReportBuffer();
        $report        = new Report($reportBuffer, $tempDirectory);
        $consoleOutput = $this->getWrappedOutput();
        $exitCode      = $this->runTasks($taskList, $report, $consoleOutput);

        // Stage 3: reporting.
        $reportBuffer->complete($exitCode === 0 ? Report::STATUS_PASSED : Report::STATUS_FAILED);
        $this->writeReports($reportBuffer, $projectConfig);

        // Stage 4. cleanup.
        $consoleOutput->writeln('Finished.', OutputInterface::VERBOSITY_VERBOSE, OutputInterface::CHANNEL_STDERR);
        $fileSystem->remove($tempDirectory);

        return $exitCode;
    }

    private function runTasks(Tasklist $taskList, Report $report, OutputInterface $output): int
    {
        $fastFinish = (bool) $this->input->getOption('fast-finish');
        $threads    = (int) $this->input->getOption('threads');
        $scheduler  = new TaskScheduler($taskList, $threads, $report, $output, $fastFinish);

        return $scheduler->run() ? 0 : 1;
    }

    /** @psalm-return array{string, list<string>} */
    private function findPhpCli(): array
    {
        $finder     = new PhpExecutableFinder();
        $executable = $finder->find();

        if (!is_string($executable)) {
            throw new RuntimeException('PHP executable not found');
        }
        /** @psalm-var list<string> $arguments */
        $arguments = $finder->findArguments();

        return [$executable, $arguments];
    }

    private function handleTask(
        PluginRegistry $plugins,
        InstalledRepository $installed,
        string $taskName,
        ProjectConfiguration $projectConfig,
        string $tempDirectory,
        Tasklist $taskList,
        int $availableThreads
    ): void {
        $configValues = $this->config->getConfigForTask($taskName);
        $plugin = $plugins->getPluginByName($configValues['plugin'] ?? $taskName);
        $pluginConfig = $configValues['config'] ?? [];
        /** @psalm-suppress PossiblyInvalidArgument - type fom findPhpCli() is not inferred */
        $environment = new Environment(
            $projectConfig,
            new TaskFactory(
                $installed->getPlugin($plugin->getName()),
                ...$this->findPhpCli()
            ),
            $tempDirectory,
            $availableThreads
        );
        if ($plugin instanceof DiagnosticsPluginInterface) {
            $configOptionsBuilder = new PluginConfigurationBuilder($plugin->getName(), 'Plugin configuration');
            $plugin->describeConfiguration($configOptionsBuilder);

            if ($configOptionsBuilder->hasDirectoriesSupport()) {
                /** @psalm-var array<string,mixed> $configValues */
                $pluginConfig += ['directories' => $configValues['directories'] ?? $this->config->getDirectories()];
            }

            try {
                /** @psalm-var array<string,mixed> $processed */
                $processed = $configOptionsBuilder->normalizeValue($pluginConfig);
                $configOptionsBuilder->validateValue($processed);
            } catch (ConfigurationValidationErrorException $exception) {
                throw $exception->withOuterPath(['tasks', $taskName, 'config']);
            } catch (Throwable $exception) {
                throw ConfigurationValidationErrorException::fromError(['tasks', $taskName, 'config'], $exception);
            }

            $configuration = new PluginConfiguration($processed);

            foreach ($plugin->createDiagnosticTasks($configuration, $environment) as $task) {
                $taskList->add($task);
            }
        }
    }

    private function writeReports(ReportBuffer $report, ProjectConfiguration $projectConfig): void
    {
        /** @psalm-suppress PossiblyInvalidCast - We know it is a string */
        $threshold = (string) $this->input->getOption('threshold');
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

        /** @psalm-var list<string> $reports */
        $reports = (array) $this->input->getOption('report');
        $targetPath = getcwd() . '/' . $projectConfig->getArtifactOutputPath();

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
            } catch (Throwable $ignored) {
                // Fallback to grep.
                $process = new Process(['grep', '-c', '^processor', '/proc/cpuinfo']);
                try {
                    $process->mustRun();
                    return (int) trim($process->getOutput());
                } catch (Throwable $ignored) {
                    // Ignore exception and return the 1 default below.
                }
            }
        }
        // Unsupported OS.
        return 1;
    }
}
