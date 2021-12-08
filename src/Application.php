<?php

declare(strict_types=1);

namespace Phpcq\Runner;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\CompleteCommand;
use Symfony\Component\Console\Command\DumpCompletionCommand;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Phpcq\Runner\Command\ExecCommand;
use Phpcq\Runner\Command\InstallCommand;
use Phpcq\Runner\Command\PlatformInformationCommand;
use Phpcq\Runner\Command\RunCommand;
use Phpcq\Runner\Command\UpdateCommand;
use Phpcq\Runner\Command\ValidateCommand;

class Application extends BaseApplication
{
    /**
     * Create a new instance.
     */
    public function __construct()
    {
        parent::__construct('phpcq', '0.0.0.1-dev-@release-date@-@git-version@');
        $this->setDefaultCommand('run');
    }

    protected function getDefaultCommands(): array
    {
        return [
            new HelpCommand(),
            new ListCommand(),
            new CompleteCommand(),
            new DumpCompletionCommand(),
            new RunCommand(),
            new UpdateCommand(),
            new InstallCommand(),
            new ValidateCommand(),
            new PlatformInformationCommand(),
            new ExecCommand()
        ];
    }

    public function getHelp()
    {
        return sprintf(
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

            Copyright (c) 2014-2020
                Christian Schiffler <c.schiffler@cyberspectrum.de>
                David Molineus <david.molineus@netzmacht.de>

            %s <info>%s</info> build date: <info>%s</info>
            EOF,
            $this->getName(),
            $this->getVersion(),
            \DateTime::createFromFormat('Y-m-d-H-i-s-T', '@release-date@')->format('Y-m-d H:i:s T')
        );
    }
}
