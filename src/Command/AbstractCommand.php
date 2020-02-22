<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use function getcwd;

abstract class AbstractCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('config', InputArgument::OPTIONAL, '', getcwd() . '/.phpcq.yaml');
    }
}
