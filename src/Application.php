<?php

declare(strict_types=1);

namespace Phpcq\Runner;

use Phar;
use Phpcq\Runner\Command\HelpCommand;
use Phpcq\Runner\Command\SelfUpdateCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\CompleteCommand;
use Symfony\Component\Console\Command\DumpCompletionCommand;
use Symfony\Component\Console\Command\ListCommand;
use Phpcq\Runner\Command\ExecCommand;
use Phpcq\Runner\Command\InstallCommand;
use Phpcq\Runner\Command\PlatformInformationCommand;
use Phpcq\Runner\Command\RunCommand;
use Phpcq\Runner\Command\UpdateCommand;
use Phpcq\Runner\Command\ValidateCommand;

use function date;

class Application extends BaseApplication
{
    /**
     * Create a new instance.
     */
    public function __construct()
    {
        parent::__construct('phpcq', '@git-version@-@release-date@');
        $this->setDefaultCommand('run');
    }

    protected function getDefaultCommands(): array
    {
        $commands = [
            new HelpCommand(),
            new ListCommand(),
            new CompleteCommand(),
            new DumpCompletionCommand(),
            new RunCommand(),
            new UpdateCommand(),
            new InstallCommand(),
            new ValidateCommand(),
            new PlatformInformationCommand(),
            new ExecCommand(),
        ];

        $pharFile = Phar::running(false);
        if ($pharFile !== '') {
            $commands[] = new SelfUpdateCommand($pharFile);
        }

        return $commands;
    }

    public function getHelp(): string
    {
        $help = sprintf(
            <<<EOF
            _____  _                                _            _
            |  __ \| |                              | |          | |
            | |__) | |__  _ __   ___ __ _   ______  | |_ __ _ ___| | __    _ __ _   _ _ __  _ __   ___ _ __
            |  ___/| '_ \| '_ \ / __/ _` | |______| | __/ _` / __| |/ /   | '__| | | | '_ \| '_ \ / _ \ '__|
            | |    | | | | |_) | (_| (_| |          | || (_| \__ \   <    | |  | |_| | | | | | | |  __/ |
            |_|    |_| |_| .__/ \___\__, |           \__\__,_|___/_|\_\   |_|   \__,_|_| |_|_| |_|\___|_|
                         | |           | |
                         |_|           |_|
            https://github.com/phpcq/phpcq/

            Copyright (c) 2014-%s
                Christian Schiffler <c.schiffler@cyberspectrum.de>
                David Molineus <david.molineus@netzmacht.de>

            %s <info>%s</info>
            EOF,
            date('Y'),
            $this->getName(),
            $this->getVersion()
        );

        $buildDate = \DateTimeImmutable::createFromFormat('Y-m-d-H-i-s-T', '@release-date@');
        if ($buildDate instanceof \DateTimeImmutable) {
            $help .= sprintf(' build date: <info>%s</info>', $buildDate->format('Y-m-d H:i:s T'));
        }

        return $help;
    }
}
