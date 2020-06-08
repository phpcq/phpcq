<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Config\BuildConfiguration;
use Phpcq\Config\ProjectConfiguration;
use Phpcq\Exception\Exception;
use Phpcq\Exception\RuntimeException;
use Phpcq\Output\BufferedOutput;
use Phpcq\Plugin\Config\PhpcqConfigurationOptionsBuilder;
use Phpcq\Plugin\PluginRegistry;
use Phpcq\PluginApi\Version10\ConfigurationPluginInterface;
use Phpcq\PluginApi\Version10\OutputInterface;
use Phpcq\PluginApi\Version10\RuntimeException as PluginApiRuntimeException;
use Phpcq\PluginApi\Version10\Task\ReportWritingTaskInterface;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Writer\CheckstyleReportWriter;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Report;
use Phpcq\Report\Writer\ConsoleWriter;
use Phpcq\Report\Writer\FileReportWriter;
use Phpcq\Report\Writer\ToolReportWriter;
use Phpcq\Task\TaskFactory;
use Phpcq\Task\Tasklist;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;

use function assert;
use function getcwd;
use function in_array;
use function is_string;

final class RunCommand extends AbstractCommand
{
    use InstalledRepositoryLoadingCommandTrait;

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
            'tool',
            InputArgument::OPTIONAL,
            'Define a specific tool which should be run'
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
            'Set the report formats which should be created. Available options are "file-report", '
            . '"tool-report" and "checkstyle".',
            ['file-report']
        );

        $this->addOption(
            'threshold',
            null,
            InputOption::VALUE_REQUIRED,
            'Set the minimum threshold for diagnostics to be reported, Available options are (in ascending order): "' .
            implode('", "', [
                ToolReportInterface::SEVERITY_INFO,
                ToolReportInterface::SEVERITY_NOTICE,
                ToolReportInterface::SEVERITY_WARNING,
                ToolReportInterface::SEVERITY_ERROR,
            ]) . '"',
            ToolReportInterface::SEVERITY_INFO
        );

        parent::configure();
    }

    protected function doExecute(): int
    {
        $projectConfig = new ProjectConfiguration(getcwd(), $this->config['directories'], $this->config['artifact']);
        $tempDirectory = sys_get_temp_dir();
        $taskList = new Tasklist();
        $reportBuffer = new ReportBuffer();
        $report = new Report($reportBuffer, $tempDirectory);
        /** @psalm-suppress PossiblyInvalidArgument */
        $taskFactory = new TaskFactory(
            $this->phpcqPath,
            $installed = $this->getInstalledRepository(true),
            ...$this->findPhpCli()
        );
        // Create build configuration
        $buildConfig = new BuildConfiguration($projectConfig, $taskFactory, $tempDirectory);
        // Load bootstraps
        $plugins = PluginRegistry::buildFromInstalledRepository($installed);

        $chain = $this->input->getArgument('chain');
        assert(is_string($chain));

        if (!isset($this->config['chains'][$chain])) {
            throw new RuntimeException(sprintf('Unknown chain "%s"', $chain));
        }

        if ($toolName = $this->input->getArgument('tool')) {
            assert(is_string($toolName));
            $this->handlePlugin($plugins, $chain, $toolName, $buildConfig, $taskList);
        } else {
            foreach (array_keys($this->config['chains'][$chain]) as $toolName) {
                $this->handlePlugin($plugins, $chain, $toolName, $buildConfig, $taskList);
            }
        }

        $consoleOutput = $this->getWrappedOutput();
        $exitCode = $this->runTasks($taskList, $report, $consoleOutput, (bool) $this->input->getOption('fast-finish'));

        $reportBuffer->complete($exitCode === 0 ? Report::STATUS_PASSED : Report::STATUS_FAILED);
        $this->writeReports($reportBuffer, $projectConfig);

        $consoleOutput->writeln('Finished.', $consoleOutput::VERBOSITY_VERBOSE, $consoleOutput::CHANNEL_STDERR);
        return $exitCode;
    }

    private function runTasks(Tasklist $taskList, Report $report, OutputInterface $output, bool $fastFinish): int
    {
        // TODO: Parallelize tasks
        $exitCode = 0;
        foreach ($taskList->getIterator() as $task) {
            if (!$task instanceof ReportWritingTaskInterface) {
                throw new RuntimeException('Task is not an instance of: ' . ReportWritingTaskInterface::class);
            }

            try {
                $toolReport = $report->addToolReport($task->getToolName());
                $task->runWithReport($toolReport);

                if ($fastFinish && $toolReport->getStatus() !== ToolReportInterface::STATUS_PASSED) {
                    return $exitCode;
                }
            } catch (PluginApiRuntimeException $throwable) {
                $this->renderException($output, $throwable);

                $exitCode = (int) $throwable->getCode();
                $exitCode = $exitCode === 0 ? 1 : $exitCode;

                if ($fastFinish) {
                    return $exitCode;
                }
            }
        }

        return $exitCode;
    }

    private function renderException(OutputInterface $output, PluginApiRuntimeException $exception): void
    {
        // Log internal exceptions on level normal - these indicate an error in phpcq caused from within a plugin.
        if ($exception instanceof Exception) {
            $output->writeln('', BufferedOutput::VERBOSITY_NORMAL, BufferedOutput::CHANNEL_STDERR);
            $output->writeln(
                'WARNING: task execution caused internal error: "' . $exception->getMessage() . '"',
                BufferedOutput::VERBOSITY_NORMAL,
                BufferedOutput::CHANNEL_STDERR
            );
            $output->writeln(
                'This is most certainly caused by a plugin misbehaving.',
                BufferedOutput::VERBOSITY_NORMAL,
                BufferedOutput::CHANNEL_STDERR
            );
            $output->writeln(
                $exception->getFile() . ' on line ' . $exception->getLine(),
                BufferedOutput::VERBOSITY_VERBOSE,
                BufferedOutput::CHANNEL_STDERR
            );
            $output->writeln(
                $exception->getTraceAsString(),
                BufferedOutput::VERBOSITY_VERBOSE,
                BufferedOutput::CHANNEL_STDERR
            );
        }

        // Normal plugin exception.
        $output->writeln(
            $exception->getMessage(),
            BufferedOutput::VERBOSITY_VERBOSE,
            BufferedOutput::CHANNEL_STDERR
        );
    }

    /** @psalm-return array{0: string, 1: array} */
    private function findPhpCli(): array
    {
        $finder     = new PhpExecutableFinder();
        $executable = $finder->find();

        if (!is_string($executable)) {
            throw new RuntimeException('PHP executable not found');
        }

        return [$executable, $finder->findArguments()];
    }

    /**
     * @param PluginRegistry     $plugins
     * @param string             $chain
     * @param string             $toolName
     * @param BuildConfiguration $buildConfig
     * @param Tasklist           $taskList
     *
     * @return void
     */
    protected function handlePlugin(
        PluginRegistry $plugins,
        string $chain,
        string $toolName,
        BuildConfiguration $buildConfig,
        Tasklist $taskList
    ): void {
        $plugin = $plugins->getPluginByName($toolName);
        $name   = $plugin->getName();

        // Initialize phar files
        if ($plugin instanceof ConfigurationPluginInterface) {
            $configOptionsBuilder = new PhpcqConfigurationOptionsBuilder();
            $configuration       = $this->config['chains'][$chain][$name]
                ?? ($this->config['tool-config'][$name] ?: []);

            $plugin->describeOptions($configOptionsBuilder);
            $options = $configOptionsBuilder->getOptions();
            $options->validateConfig($configuration);

            foreach ($plugin->processConfig($configuration, $buildConfig) as $task) {
                $taskList->add($task);
            }
        }
    }

    private function writeReports(ReportBuffer $report, ProjectConfiguration $projectConfig): void
    {
        /** @psalm-suppress PossiblyInvalidCast - We know it is a string */
        $threshold  = (string) $this->input->getOption('threshold');

        ConsoleWriter::writeReport(
            $this->output,
            new SymfonyStyle($this->input, $this->output),
            $report,
            $threshold,
            $this->getWrapWidth()
        );

        $reports = (array) $this->input->getOption('report');
        $targetPath = getcwd() . '/' . $projectConfig->getArtifactOutputPath();

        if (in_array('tool-report', $reports, true)) {
            ToolReportWriter::writeReport($targetPath, $report, $threshold);
        }

        if (in_array('file-report', $reports, true)) {
            FileReportWriter::writeReport($targetPath, $report, $threshold);
        }

        if (in_array('checkstyle', $reports, true)) {
            CheckstyleReportWriter::writeReport($targetPath, $report, $threshold);
        }

        // Clean up attachments.
        $fileSystem = new Filesystem();
        foreach ($report->getToolReports() as $toolReport) {
            foreach ($toolReport->getAttachments() as $attachment) {
                $fileSystem->remove($attachment->getAbsolutePath());
            }
        }
    }
}
