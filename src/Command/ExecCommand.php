<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Phpcq\PluginApi\Version10\ExecPluginInterface;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\Task\OutputWritingTaskInterface;
use Phpcq\Runner\Environment;
use Phpcq\PluginApi\Version10\Exception\RuntimeException as PluginApiRuntimeException;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\Runner\Output\BufferedOutput;
use Phpcq\Runner\Plugin\PluginRegistry;
use Phpcq\Runner\Task\SingleProcessTaskFactory;
use Phpcq\Runner\Task\TaskFactory;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;

final class ExecCommand extends AbstractCommand
{
    use InstalledRepositoryLoadingCommandTrait;

    /**
     * If no '--' is to be found in the args (used to separate options for phpcq from the args and options for the tool,
     * insert it just before the tool name.
     */
    public static function prepareArguments(array $argv): array
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
            'application',
            InputArgument::OPTIONAL,
            'The application which should be executed'
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
        $installed     = $this->getInstalledRepository(true);
        $plugins       = PluginRegistry::buildFromInstalledRepository($installed);
        $projectConfig = $this->createProjectConfiguration(1);
        $tempDirectory = $this->createTempDirectory();

        $fullName = $this->input->getArgument('application');
        if (!is_string($fullName)) {
            throw new RuntimeException('You have to pass the application name');
        }

        [$pluginName, $applicationName] = array_pad(explode(':', $fullName), 2, null);

        $instance = $plugins->getPluginByName((string) $pluginName);
        if (! $instance instanceof ExecPluginInterface) {
            throw new \RuntimeException(
                sprintf('Plugin "%s" does not implement the "ExecPluginInterface" interface', $instance->getName())
            );
        }

        /** @psalm-var list<string> $toolArguments */
        $toolArguments = $this->input->getArgument('args');
        $environment = new Environment(
            $projectConfig,
            new SingleProcessTaskFactory(new TaskFactory(
                $installed->getPlugin($instance->getName()),
                ...$this->findPhpCli()
            )),
            $tempDirectory,
            1
        );

        // Wrap console output
        $consoleOutput = $this->getWrappedOutput();
        $exitCode      = 0;
        $taskOutput    = new BufferedOutput($consoleOutput);
        $task          = $instance->createExecTask($applicationName, $toolArguments, $environment);

        if (! $task instanceof OutputWritingTaskInterface) {
            throw new RuntimeException('Task has to be an instance of ' . OutputWritingTaskInterface::class);
        }

        try {
            $task->runForOutput($taskOutput);
        } catch (PluginApiRuntimeException $throwable) {
            $exitCode = (int) $throwable->getCode();
            $exitCode = $exitCode === 0 ? 1 : $exitCode;
            $taskOutput->writeln(
                $throwable->getMessage(),
                OutputInterface::VERBOSITY_NORMAL,
                OutputInterface::CHANNEL_STDERR
            );
        }

        $taskOutput->release();

        return $exitCode;
    }
}
