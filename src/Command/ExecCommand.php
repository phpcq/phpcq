<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Exception\RuntimeException as PluginApiRuntimeException;
use Phpcq\Runner\Output\BufferedOutput;
use Phpcq\PluginApi\Version10\Task\OutputWritingTaskInterface;
use Phpcq\Runner\Task\TaskFactory;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
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
        if (false !== array_search('--', $argv, true)) {
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
            'plugin',
            InputArgument::REQUIRED,
            'The plugin which provides the tool'
        );

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

    protected function doExecute(): int
    {
        // FIXME: Introduce own interface and rewrite the command

        /** @psalm-suppress PossiblyInvalidArgument */
        $taskFactory = new TaskFactory(
            $this->getInstalledRepository(true)->getPlugin($this->input->getArgument('plugin')),
            ...$this->findPhpCli()
        );

        $toolName = $this->input->getArgument('tool');
        assert(is_string($toolName));

        /** @psalm-var list<string> $toolArguments */
        $toolArguments = $this->input->getArgument('args');
        $task = $taskFactory
            ->buildRunPhar($toolName, $toolArguments)
            ->forceSingleProcess()
            ->withWorkingDirectory(getcwd())
            ->build();

        if (! $task instanceof OutputWritingTaskInterface) {
            throw new RuntimeException('Task is not an instance of: ' . OutputWritingTaskInterface::class);
        }

        // Wrap console output
        $consoleOutput = $this->getWrappedOutput();
        // Execute task.
        $exitCode = 0;
        $taskOutput = new BufferedOutput($consoleOutput);
        try {
            $task->runForOutput($taskOutput);
        } catch (PluginApiRuntimeException $throwable) {
            $exitCode = (int) $throwable->getCode();
            $exitCode = $exitCode === 0 ? 1 : $exitCode;
            $taskOutput->writeln(
                $throwable->getMessage(),
                BufferedOutput::VERBOSITY_NORMAL,
                BufferedOutput::CHANNEL_STDERR
            );
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
