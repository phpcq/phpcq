<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Config\Builder\PluginConfigurationBuilder;
use Phpcq\Config\PluginConfiguration;
use Phpcq\Config\ProjectConfiguration;
use Phpcq\Exception\ConfigurationValidationErrorException;
use Phpcq\Exception\RuntimeException;
use Phpcq\Plugin\PluginRegistry;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\Report\ToolReportInterface;
use Phpcq\Report\Report;
use Phpcq\Report\Writer\CheckstyleReportWriter;
use Phpcq\Environment;
use Phpcq\PluginApi\Version10\DiagnosticsPluginInterface;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Writer\ConsoleWriter;
use Phpcq\Report\Writer\FileReportWriter;
use Phpcq\Report\Writer\GithubActionConsoleWriter;
use Phpcq\Report\Writer\ToolReportWriter;
use Phpcq\Task\TaskFactory;
use Phpcq\Task\Tasklist;
use Phpcq\Task\TaskScheduler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

use function array_key_exists;
use function array_keys;
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
            'Set the report formats which should be created. Available options are <info>file-report</info>, '
            . '<info>tool-report</info> and <info>checkstyle</info>".',
            ['file-report']
        );

        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'Set a specific console output format. Available options are <info>default</info> and '
            . '<info>github-action</info>',
            'default'
        );

        $this->addOption(
            'threshold',
            null,
            InputOption::VALUE_REQUIRED,
            'Set the minimum threshold for diagnostics to be reported, Available options are (in ascending order): "' .
            implode('", "', [
                ToolReportInterface::SEVERITY_NONE,
                ToolReportInterface::SEVERITY_INFO,
                ToolReportInterface::SEVERITY_MINOR,
                ToolReportInterface::SEVERITY_MARGINAL,
                ToolReportInterface::SEVERITY_MAJOR,
                ToolReportInterface::SEVERITY_FATAL,
            ]) . '"',
            ToolReportInterface::SEVERITY_INFO
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
        $artifactDir = $this->config->getArtifactDir();
        $fileSystem = new Filesystem();
        $projectConfig = new ProjectConfiguration(getcwd(), $this->config->getDirectories(), $artifactDir);

        $tempDirectory = sys_get_temp_dir() . '/' . uniqid('phpcq-');
        $fileSystem->mkdir($tempDirectory);

        /** @psalm-suppress PossiblyInvalidArgument */
        $taskFactory = new TaskFactory(
            $this->phpcqPath,
            $installed = $this->getInstalledRepository(true),
            ...$this->findPhpCli()
        );

        $chain = $this->input->getArgument('chain');
        assert(is_string($chain));

        $chains = $this->config->getChains();
        if (!isset($chains[$chain])) {
            throw new RuntimeException(sprintf('Unknown chain "%s"', $chain));
        }

        $environment = new Environment($projectConfig, $taskFactory, $tempDirectory);
        $outputPath = $environment->getProjectConfiguration()->getArtifactOutputPath();
        $fileSystem->remove($outputPath);
        $fileSystem->mkdir($outputPath);

        $plugins = PluginRegistry::buildFromInstalledRepository($installed);
        $taskList = new Tasklist();
        if ($toolName = $this->input->getArgument('tool')) {
            assert(is_string($toolName));
            $this->handlePlugin($plugins, $chain, $toolName, $environment, $taskList);
        } else {
            foreach (array_keys($chains[$chain]) as $toolName) {
                $this->handlePlugin($plugins, $chain, $toolName, $environment, $taskList);
            }
        }

        // Stage 2: execution.
        $reportBuffer  = new ReportBuffer();
        $report        = new Report($reportBuffer, $installed, $tempDirectory);
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
     * @param PluginRegistry $plugins
     * @param string         $chain
     * @param string         $toolName
     * @param Environment    $buildConfig
     * @param Tasklist       $taskList
     *
     * @return void
     */
    protected function handlePlugin(
        PluginRegistry $plugins,
        string $chain,
        string $toolName,
        Environment $buildConfig,
        Tasklist $taskList
    ): void {
        $plugin = $plugins->getPluginByName($toolName);
        $name   = $plugin->getName();

        // Initialize phar files
        if ($plugin instanceof DiagnosticsPluginInterface) {
            $chains               = $this->config->getChains();
            $toolConfig           = $this->config->getToolConfig();
            $configOptionsBuilder = new PluginConfigurationBuilder($plugin->getName(), 'Plugin configuration');
            $configuration        = $chains[$chain][$name] ?? $toolConfig[$name];

            $plugin->describeConfiguration($configOptionsBuilder);
            if (!$configOptionsBuilder->hasDirectoriesSupport()) {
                unset($configuration['directories']);

                $processed = $configOptionsBuilder->normalizeValue($configuration);
                $configOptionsBuilder->validateValue($processed);
                /** @psalm-var array<string,mixed> $processed */
                $configuration = new PluginConfiguration($processed);

                foreach ($plugin->createDiagnosticTasks($configuration, $buildConfig) as $task) {
                    $taskList->add($task);
                }
                return;
            }

            /** @psalm-var array<string,mixed> $configuration */
            //$configuration['directories'] = $configuration['directories'] ?: $this->config->getDirectories();
            foreach ($this->processDirectories($configuration) as $config) {
                try {
                    $processed = $configOptionsBuilder->normalizeValue($config);
                    $configOptionsBuilder->validateValue($processed);
                } catch (ConfigurationValidationErrorException $exception) {
                    throw $exception->withOuterPath([$name]);
                } catch (Throwable $exception) {
                    throw ConfigurationValidationErrorException::fromError([$name], $exception);
                }

                /** @psalm-var array<string,mixed> $processed */
                $configuration = new PluginConfiguration($processed);

                foreach ($plugin->createDiagnosticTasks($configuration, $buildConfig) as $task) {
                    $taskList->add($task);
                }
            }
        }
    }

    /**
     * @psalm-param array<string,mixed> $configuration
     * @psalm-return list<array<string,mixed>>
     */
    private function processDirectories(array $configuration): array
    {
        assert(array_key_exists('directories', $configuration));
        /** @psalm-var array<string, array<string,mixed>|null> $directories */
        $directories                  = $configuration['directories'];
        $configuration['directories'] = [];
        $configs                      = [$configuration];

        foreach ($directories as $directory => $config) {
            if (null === $config) {
                /** @psalm-suppress MixedArrayAssignment */
                $configs[0]['directories'][] = $directory;
                continue;
            }

            $configs[] = $config;
        }

        return $configs;
    }

    private function writeReports(ReportBuffer $report, ProjectConfiguration $projectConfig): void
    {
        /** @psalm-suppress PossiblyInvalidCast - We know it is a string */
        $threshold  = (string) $this->input->getOption('threshold');

        if ($this->input->getOption('output') === 'github-action') {
            GithubActionConsoleWriter::writeReport($this->output, $report);
        } else {
            ConsoleWriter::writeReport(
                $this->output,
                new SymfonyStyle($this->input, $this->output),
                $report,
                $threshold,
                $this->getWrapWidth()
            );
        }

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
