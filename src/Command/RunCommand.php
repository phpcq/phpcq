<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Config\BuildConfiguration;
use Phpcq\Config\ProjectConfiguration;
use Phpcq\ConfigLoader;
use Phpcq\Exception\RuntimeException;
use Phpcq\FileDownloader;
use Phpcq\Output\BufferedOutput;
use Phpcq\Output\SymfonyConsoleOutput;
use Phpcq\Output\SymfonyOutput;
use Phpcq\Platform\PlatformInformation;
use Phpcq\Plugin\ConfigurationPluginInterface;
use Phpcq\Plugin\PluginRegistry;
use Phpcq\Repository\JsonRepositoryLoader;
use Phpcq\Repository\RepositoryInterface;
use Phpcq\Task\TaskFactory;
use Phpcq\Task\Tasklist;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;

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

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $phpcqPath = $input->getOption('tools');
        if (!is_dir($phpcqPath)) {
            mkdir($phpcqPath, 0777, true);
        }
        $cachePath = $input->getOption('cache');
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0777, true);
        }
        if ($output->isVeryVerbose()) {
            $output->writeln('Using HOME: ' . $phpcqPath);
            $output->writeln('Using CACHE: ' . $cachePath);
        }
        $configFile = $input->getOption('config');
        $config     = ConfigLoader::load($configFile);

        $projectConfig = new ProjectConfiguration(getcwd(), $config['directories'], $config['artifact']);
        $taskList = new Tasklist();
        $taskFactory = new TaskFactory(
            $phpcqPath,
            $this->getInstalledRepository($phpcqPath, $cachePath),
            ...$this->findPhpCli()
        );
        // Create build configuration
        $buildConfig = new BuildConfiguration($projectConfig, $taskFactory, sys_get_temp_dir());
        // Load bootstraps
        $plugins = PluginRegistry::buildFromPath($phpcqPath);

        if ($toolName = $input->getArgument('tool')) {
            $this->handlePlugin($plugins, $toolName, $config, $buildConfig, $taskList);
        } else {
            foreach ($config['tools'] as $toolName => $tool) {
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
        foreach ($taskList->getIterator() as $task) {
            $taskOutput = new BufferedOutput($consoleOutput);
            $task->run($taskOutput);
            $taskOutput->release();
        }

        return 0;
    }

    private function getInstalledRepository(string $phpcqPath, string $cachePath): RepositoryInterface
    {
        if (!is_file($phpcqPath . '/installed.json')) {
            throw new RuntimeException('Please install the tools first ("phpcq update").');
        }
        $loader = new JsonRepositoryLoader(new PlatformInformation(), new FileDownloader($cachePath));

        return $loader->loadFile($phpcqPath . '/installed.json');
    }

    private function findPhpCli(): array
    {
        $finder = new PhpExecutableFinder();

        return [$finder->find(), $finder->findArguments()];
    }

    /**
     * @param PluginRegistry $plugins
     * @param $toolName
     * @param $config
     * @param BuildConfiguration $buildConfig
     * @param Tasklist $taskList
     *
     * @return void
     */
    protected function handlePlugin(PluginRegistry $plugins, $toolName, $config, BuildConfiguration $buildConfig, Tasklist $taskList): void
    {
        $plugin = $plugins->getPluginByName($toolName);
        $name   = $plugin->getName();

        // Initialize phar files
        if ($plugin instanceof ConfigurationPluginInterface) {
            $configuration = $config[$name] ?? [];
            $plugin->validateConfig($configuration);
            foreach ($plugin->processConfig($configuration, $buildConfig) as $task) {
                $taskList->add($task);
            }
        }
    }
}
