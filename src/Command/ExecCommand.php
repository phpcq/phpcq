<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Exception\RuntimeException;
use Phpcq\FileDownloader;
use Phpcq\Output\BufferedOutput;
use Phpcq\Output\SymfonyConsoleOutput;
use Phpcq\Output\SymfonyOutput;
use Phpcq\Platform\PlatformInformation;
use Phpcq\Repository\JsonRepositoryLoader;
use Phpcq\Repository\RepositoryInterface;
use Phpcq\Task\TaskFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use function assert;
use function is_string;

final class ExecCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('exec')->setDescription('Execute a tool with the passed arguments');

        $this->addArgument(
            'tool',
            InputArgument::REQUIRED,
            'The tool to be run'
        );

        $this->addArgument(
            'args',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'Optional options and arguments to pass to the tool'
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

        /** @psalm-suppress PossiblyInvalidArgument */
        $taskFactory = new TaskFactory(
            $phpcqPath,
            $this->getInstalledRepository($phpcqPath, $cachePath),
            ...$this->findPhpCli()
        );

        $toolName = $input->getArgument('tool');
        assert(is_string($toolName));

        /** @var array $toolArguments */
        $toolArguments = $input->getArgument('args');
        $task = $taskFactory
            ->buildRunPhar($toolName, $toolArguments)
            ->withWorkingDirectory(getcwd())
            ->build();

        // Wrap console output
        if ($output instanceof ConsoleOutputInterface) {
            $consoleOutput = new SymfonyConsoleOutput($output);
        } else {
            $consoleOutput = new SymfonyOutput($output);
        }

        // Execute task.
        $exitCode = 0;
        $taskOutput = new BufferedOutput($consoleOutput);
        try {
            $task->run($taskOutput);
        } catch (RuntimeException $throwable) {
            $taskOutput->writeln($throwable->getMessage(), SymfonyOutput::VERBOSITY_NORMAL, SymfonyOutput::CHANNEL_STRERR);
            $taskOutput->release();
            return (int) $throwable->getCode();
        }
        $taskOutput->release();

        return $exitCode;
    }

    private function getInstalledRepository(string $phpcqPath, string $cachePath): RepositoryInterface
    {
        if (!is_file($phpcqPath . '/installed.json')) {
            throw new RuntimeException('Please install the tools first ("phpcq update").');
        }
        $loader = new JsonRepositoryLoader(
            PlatformInformation::createFromCurrentPlatform(),
            new FileDownloader($cachePath)
        );

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
}
