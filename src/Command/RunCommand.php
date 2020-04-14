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
use Phpcq\Task\TaskFactory;
use Phpcq\Task\Tasklist;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\PhpExecutableFinder;
use function assert;
use function is_string;

final class RunCommand extends AbstractCommand
{
    use InstalledRepositoryLoadingCommandTrait;

    protected function configure(): void
    {
        $this->setName('run')->setDescription('Run configured build tasks');

        $this->addArgument(
            'tool',
            InputArgument::OPTIONAL,
            'Define a specific tool which should be run'
        );
        $this->addOption(
            'keep-going',
            'k',
            InputOption::VALUE_NONE,
            'Keep going and execute all tasks.'
        );

        parent::configure();
    }

    protected function doExecute(): int
    {
        $projectConfig = new ProjectConfiguration(getcwd(), $this->config['directories'], $this->config['artifact']);
        $taskList = new Tasklist();
        /** @psalm-suppress PossiblyInvalidArgument */
        $taskFactory = new TaskFactory(
            $this->phpcqPath,
            $installed = $this->getInstalledRepository(true),
            ...$this->findPhpCli()
        );
        // Create build configuration
        $buildConfig = new BuildConfiguration($projectConfig, $taskFactory, sys_get_temp_dir());
        // Load bootstraps
        $plugins = PluginRegistry::buildFromInstalledRepository($installed);

        if ($toolName = $this->input->getArgument('tool')) {
            assert(is_string($toolName));
            $this->handlePlugin($plugins, $toolName, $this->config, $buildConfig, $taskList);
        } else {
            foreach (array_keys($this->config['tools']) as $toolName) {
                $this->handlePlugin($plugins, $toolName, $this->config, $buildConfig, $taskList);
            }
        }

        $consoleOutput = $this->getWrappedOutput();
        // TODO: Parallelize tasks
        // Execute task list
        $exitCode = 0;
        $keepGoing = $this->input->getOption('keep-going');
        foreach ($taskList->getIterator() as $task) {
            $taskOutput = new BufferedOutput($consoleOutput);
            try {
                $task->run($taskOutput);
            } catch (RuntimeException $throwable) {
                $taskOutput->writeln($throwable->getMessage(), BufferedOutput::VERBOSITY_NORMAL, BufferedOutput::CHANNEL_STRERR);
                if (!$keepGoing) {
                    $taskOutput->release();
                    return (int) $throwable->getCode();
                }
                $exitCode = (int) $throwable->getCode();
            }
            $taskOutput->release();
        }

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
     * @param PluginRegistry $plugins
     * @param string $toolName
     * @param array $config
     * @param BuildConfiguration $buildConfig
     * @param Tasklist $taskList
     *
     * @return void
     */
    protected function handlePlugin(PluginRegistry $plugins, string $toolName, array $config, BuildConfiguration $buildConfig, Tasklist $taskList): void
    {
        $plugin = $plugins->getPluginByName($toolName);
        $name   = $plugin->getName();

        // Initialize phar files
        if ($plugin instanceof ConfigurationPluginInterface) {
            $configOptionsBuilder = new PhpcqConfigurationOptionsBuilder();
            $configuration       = $config[$name] ?? [];

            $plugin->describeOptions($configOptionsBuilder);
            $options = $configOptionsBuilder->getOptions();
            $options->validateConfig($configuration);

            foreach ($plugin->processConfig($configuration, $buildConfig) as $task) {
                $taskList->add($task);
            }
        }
    }
}
