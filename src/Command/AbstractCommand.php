<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use function getcwd;

abstract class AbstractCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'The configuration file to use',
            getcwd() . '/.phpcq.yaml'
        );
        $this->addOption(
            'tools',
            't',
            InputOption::VALUE_REQUIRED,
            'Path to the phpcq tool directory',
            getcwd() . '/vendor/phpcq'
        );
        $this->addOption(
            'cache',
            'x',
            InputOption::VALUE_REQUIRED,
            'Path to the phpcq cache directory',
            getenv('HOME') . '/.cache/phpcq'
        );
    }
}
