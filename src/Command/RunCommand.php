<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Config\BuildConfiguration;
use Phpcq\Config\ProjectConfiguration;
use Phpcq\ConfigLoader;
use Phpcq\Exception\RuntimeException;
use Phpcq\Output\BufferedOutput;
use Phpcq\Output\SymfonyConsoleOutput;
use Phpcq\Output\SymfonyOutput;
use Phpcq\Platform\PlatformInformation;
use Phpcq\Plugin\Config\PhpcqConfigurationOptionsBuilder;
use Phpcq\Plugin\PluginRegistry;
use Phpcq\PluginApi\Version10\ConfigurationPluginInterface;
use Phpcq\Repository\InstalledRepositoryLoader;
use Phpcq\Repository\RepositoryInterface;
use Phpcq\Task\TaskFactory;
use Phpcq\Task\Tasklist;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use function assert;
use function is_string;

final class RunCommand extends AbstractCommand
{
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $phpcqPath = $input->getOption('tools');
        assert(is_string($phpcqPath));
        $this->createDirectory($phpcqPath);

        $cachePath = $input->getOption('cache');
        assert(is_string($cachePath));
        $this->createDirectory($cachePath);

        if ($output->isVeryVerbose()) {
            $output->writeln('Using HOME: ' . $phpcqPath);
            $output->writeln('Using CACHE: ' . $cachePath);
        }
        $configFile = $input->getOption('config');
        assert(is_string($configFile));
        $config     = ConfigLoader::load($configFile);

        $projectConfig = new ProjectConfiguration(getcwd(), $config['directories'], $config['artifact']);
        $taskList = new Tasklist();
        /** @psalm-suppress PossiblyInvalidArgument */
        $taskFactory = new TaskFactory(
            $phpcqPath,
            $installed = $this->getInstalledRepository($phpcqPath),
            ...$this->findPhpCli()
        );
        // Create build configuration
        $buildConfig = new BuildConfiguration($projectConfig, $taskFactory, sys_get_temp_dir());
        // Load bootstraps
        $plugins = PluginRegistry::buildFromInstalledRepository($installed);

        if ($toolName = $input->getArgument('tool')) {
            assert(is_string($toolName));
            $this->handlePlugin($plugins, $toolName, $config, $buildConfig, $taskList);
        } else {
            foreach (array_keys($config['tools']) as $toolName) {
                $this->handlePlugin($plugins, $toolName, $config, $buildConfig, $taskList);
            }
        }

        // Wrap console output
        if ($output instanceof ConsoleOutputInterface) {
            $consoleOutput = new SymfonyConsoleOutput($output);
        } else {
            $consoleOutput = new SymfonyOutput($output);
        }

        // TODO: Parallelize tasks
        // Execute task list
        $exitCode = 0;
        $keepGoing = $input->getOption('keep-going');
        foreach ($taskList->getIterator() as $task) {
            $taskOutput = new BufferedOutput($consoleOutput);
            try {
                $task->run($taskOutput);
            } catch (RuntimeException $throwable) {
                $taskOutput->writeln($throwable->getMessage(), SymfonyOutput::VERBOSITY_NORMAL, SymfonyOutput::CHANNEL_STRERR);
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

    private function getInstalledRepository(string $phpcqPath): RepositoryInterface
    {
        if (!is_file($phpcqPath . '/installed.json')) {
            throw new RuntimeException('Please install the tools first ("phpcq update").');
        }
        $loader = new InstalledRepositoryLoader(PlatformInformation::createFromCurrentPlatform());

        return $loader->loadFile($phpcqPath . '/installed.json');
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
     * @param array[] $config
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
