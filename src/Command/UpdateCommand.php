<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\ConfigLoader;
use Phpcq\FileDownloader;
use Phpcq\Output\SymfonyConsoleOutput;
use Phpcq\Output\SymfonyOutput;
use Phpcq\Platform\PlatformInformation;
use Phpcq\Repository\JsonRepositoryLoader;
use Phpcq\Repository\RepositoryFactory;
use Phpcq\ToolUpdate\UpdateCalculator;
use Phpcq\ToolUpdate\UpdateExecutor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function assert;
use function is_string;

final class UpdateCommand extends AbstractCommand
{
    use InstalledRepositoryLoadingCommandTrait;

    protected function configure(): void
    {
        $this->setName('update')->setDescription('Update the phpcq installation');
        $this->addOption(
            'cache',
            'x',
            InputOption::VALUE_REQUIRED,
            'Path to the phpcq cache directory',
            (getenv('HOME') ?: sys_get_temp_dir()) . '/.cache/phpcq'
        );
        $this->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Dry run'
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

        $platformInformation = PlatformInformation::createFromCurrentPlatform();
        $configFile       = $input->getOption('config');
        assert(is_string($configFile));
        $config           = ConfigLoader::load($configFile);
        $downloader       = new FileDownloader($cachePath, $config['auth'] ?? []);
        $repositoryLoader = new JsonRepositoryLoader($platformInformation, $downloader, true);
        $factory          = new RepositoryFactory($repositoryLoader);
        // Download repositories
        $pool = $factory->buildPool($config['repositories'] ?? []);

        // Wrap console output
        if ($output instanceof ConsoleOutputInterface) {
            $consoleOutput = new SymfonyConsoleOutput($output);
        } else {
            $consoleOutput = new SymfonyOutput($output);
        }

        $calculator = new UpdateCalculator($this->getInstalledRepository($phpcqPath, false), $pool, $consoleOutput);
        $tasks = $calculator->calculate($config['tools']);

        if ($input->getOption('dry-run')) {
            foreach ($tasks as $task) {
                $output->writeln($task['message']);
            }
            return 0;
        }
        $executor = new UpdateExecutor($platformInformation, $downloader, $phpcqPath, $consoleOutput);
        $executor->execute($tasks);

        return 0;
    }
}
