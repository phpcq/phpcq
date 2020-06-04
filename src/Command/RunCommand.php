<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Config\BuildConfiguration;
use Phpcq\Config\ProjectConfiguration;
use Phpcq\Exception\RuntimeException;
use Phpcq\Output\BufferedOutput;
use Phpcq\Plugin\Config\PhpcqConfigurationOptionsBuilder;
use Phpcq\Plugin\PluginRegistry;
use Phpcq\PluginApi\Version10\ConfigurationPluginInterface;
use Phpcq\PluginApi\Version10\RuntimeException as PluginApiRuntimeException;
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
            'Set the report formats which should be created. Available options are tool-report and checkstyle',
        );

        parent::configure();
    }

    protected function doExecute(): int
    {
        $projectConfig = new ProjectConfiguration(getcwd(), $this->config['directories'], $this->config['artifact']);
        $tempDirectory = sys_get_temp_dir();
        $taskList = new Tasklist();
        $report = new ReportBuffer();
        /** @psalm-suppress PossiblyInvalidArgument */
        $taskFactory = new TaskFactory(
            $this->phpcqPath,
            $installed = $this->getInstalledRepository(true),
            new Report($report, $tempDirectory),
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
        // TODO: Parallelize tasks
        // Execute task list
        $exitCode = 0;
        $fastFinish = $this->input->getOption('fast-finish');
        foreach ($taskList->getIterator() as $task) {
            $taskOutput = new BufferedOutput($consoleOutput);
            try {
                $task->run($taskOutput);
            } catch (PluginApiRuntimeException $throwable) {
                $taskOutput->writeln(
                    $throwable->getMessage(),
                    BufferedOutput::VERBOSITY_NORMAL,
                    BufferedOutput::CHANNEL_STDERR
                );
                $exitCode = (int) $throwable->getCode();
                $exitCode = $exitCode === 0 ? 1 : $exitCode;

                if ($fastFinish) {
                    $taskOutput->release();
                    break;
                }
            }
            $taskOutput->release();
        }

        $report->complete($exitCode === 0 ? Report::STATUS_PASSED : Report::STATUS_FAILED);
        $this->writeReports($report, $projectConfig);

        ConsoleWriter::writeReport($this->output, $report);

        $consoleOutput->writeln('Finished.', $consoleOutput::VERBOSITY_VERBOSE, $consoleOutput::CHANNEL_STDERR);
        return $exitCode;
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
        $reports = (array) $this->input->getOption('report');

        if (in_array('tool-report', $reports, true)) {
            ToolReportWriter::writeReport(getcwd() . '/' . $projectConfig->getArtifactOutputPath(), $report);
        }

        if (in_array('file-report', $reports, true)) {
            FileReportWriter::writeReport(getcwd() . '/' . $projectConfig->getArtifactOutputPath(), $report);
        }

        if (in_array('checkstyle', $reports, true)) {
            CheckstyleReportWriter::writeReport(getcwd() . '/' . $projectConfig->getArtifactOutputPath(), $report);
        }
    }
}
