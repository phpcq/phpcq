<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Exception\RuntimeException;
use Phpcq\Output\BufferedOutput;
use Phpcq\Output\SymfonyConsoleOutput;
use Phpcq\Output\SymfonyOutput;
use Phpcq\Task\TaskFactory;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use function assert;
use function is_string;

final class ExecCommand extends AbstractCommand
{
    use InstalledRepositoryLoadingCommandTrait;

    /**
     * If no '--' is to be found in the args (used to separate options for phpcq from the args and options for the tool,
     * insert it just before the tool name.
     */
    public static function prepare(array $argv): array
    {
        if (false !== array_search('--', $argv)) {
            return $argv;
        }
        $argPos = array_search('exec', $argv);
        assert(is_int($argPos));
        // Use temp input to extract second argument which is the tool name we want.
        $tempInput = new ArgvInput(array_slice($argv, $argPos));
        if (null !== $toolName = $tempInput->getFirstArgument()) {
            $toolPos = array_search($toolName, $argv);
            assert(is_int($toolPos));
            $argv = array_merge(
                array_slice($argv, 0, $argPos),
                array_slice($argv, $argPos, $toolPos - $argPos),
                ['--'],
                array_slice($argv, $toolPos)
            );
        }

        return $argv;
    }

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

        if ($output->isVeryVerbose()) {
            $output->writeln('Using HOME: ' . $phpcqPath);
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        $taskFactory = new TaskFactory(
            $phpcqPath,
            $this->getInstalledRepository($phpcqPath),
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
