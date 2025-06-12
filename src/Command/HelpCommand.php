<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Phpcq\Runner\Console\Definition\ExecTaskDefinitionBuilder;
use Phpcq\Runner\Console\Descriptor\ApplicationDescriptor;
use Phpcq\Runner\Console\Descriptor\CommandDescriptor;
use Phpcq\Runner\Console\Descriptor\ExecTaskDescriptor;
use Phpcq\Runner\Plugin\PluginRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand as BaseHelpCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HelpCommand extends AbstractCommand
{
    use InstalledRepositoryLoadingCommandTrait;

    protected static $defaultName = 'help';

    /** @var Command|null */
    private $command;

    /** @var BaseHelpCommand */
    private $help;

    public function __construct(?string $name = null)
    {
        // Set before the parent constructor is called
        $this->help = new BaseHelpCommand();

        parent::__construct($name);
    }

    public function setCommand(Command $command): void
    {
        $this->command = $command;
    }

    #[\Override]
    protected function configure(): void
    {
        parent::configure();

        $this->ignoreValidationErrors();

        $this->addArgument(
            'application',
            InputArgument::OPTIONAL,
            'The application name, only available for exec command'
        );
        foreach ($this->help->getDefinition()->getArguments() as $argument) {
            $mode = $argument->isRequired() ? InputArgument::REQUIRED : InputArgument::OPTIONAL;
            if ($argument->isArray()) {
                $mode |= InputArgument::IS_ARRAY;
            }
            $this->addArgument(
                $argument->getName(),
                $mode,
                $argument->getDescription(),
                $argument->getName() === 'command_name' ? null : $argument->getDefault()
            );
        }

        foreach ($this->help->getDefinition()->getOptions() as $option) {
            $mode = $option->isValueRequired() ? InputOption::VALUE_REQUIRED : InputOption::VALUE_OPTIONAL;
            if ($option->isArray()) {
                $mode |= InputOption::VALUE_IS_ARRAY;
            }

            $this->addOption(
                $option->getName(),
                $option->getShortcut(),
                $mode,
                $option->getDescription(),
                $option->getDefault()
            );
        }

        $this->setDescription($this->help->getDescription());
        $this->setHelp($this->help->getHelp());
        $this->addUsage('[options] exec --help <application>');
        $this->addUsage('[options] exec --help <application> <command_name>');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exitCode = 0;

        if ($this->shouldRunBaseHelp($input)) {
            $this->help->setApplication($this->getApplication());

            if ($this->command) {
                $this->help->setCommand($this->command);
            }

            $commandName = (string) $input->getArgument('application') ?: 'help';
            $inputClone = clone $input;
            $inputClone->setArgument('command_name', $commandName);

            $exitCode = $this->help->run($inputClone, $output);
        }

        if (!$this->shouldExtendHelp($input)) {
            return $exitCode;
        }

        return parent::execute($input, $output);
    }

    #[\Override]
    protected function doExecute(): int
    {
        $installed     = $this->getInstalledRepository(true);
        $plugins       = PluginRegistry::buildFromInstalledRepository($installed);
        $projectConfig = $this->createProjectConfiguration(1);

        $definitionBuilder = new ExecTaskDefinitionBuilder(
            $projectConfig,
            $plugins,
            $installed,
            $this->findPhpCli(),
            $this->createTempDirectory()
        );
        $definition = $definitionBuilder->build();

        /** @var string|null $applicationName */
        $applicationName = $this->input->getArgument('application');

        if (null === $applicationName) {
            $descriptor = new ExecTaskDescriptor($this->output);
            $descriptor->describe($definition, $this->getDefinition());

            return 0;
        }

        /** @var string|null $command */
        $command = $this->input->getArgument('command_name');
        if (null !== $command) {
            $descriptor = new CommandDescriptor($this->output);
            $application = $definition->getApplication($applicationName);
            $descriptor->describe($application->getName(), $application->getCommand($command));

            return 0;
        }

        $descriptor = new ApplicationDescriptor($this->output);
        $descriptor->describe($definition->getApplication($applicationName));

        return 0;
    }

    private function shouldRunBaseHelp(InputInterface $input): bool
    {
        if (!$this->shouldExtendHelp($input)) {
            return true;
        }

        if ($input->getArgument('application')) {
            return false;
        }

        if ($input->getArgument('command_name')) {
            return false;
        }

        return true;
    }

    protected function shouldExtendHelp(InputInterface $input): bool
    {
        return $input->getOption('format') === 'txt' && $this->command instanceof ExecCommand;
    }
}
