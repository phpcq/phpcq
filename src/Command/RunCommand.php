<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\ConfigLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function getcwd;

final class RunCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('config', InputArgument::OPTIONAL, '', getcwd() . '/.phpcq.yaml');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configFile  = $input->getArgument('config');
        $config      = ConfigLoader::load($configFile);

        // Download repositories
        // Tools laden, die benötigt werden
        // Passende Bootstraps laden
        // Initialisierung der Phars
        // Create build configuration
        // Execute task list
    }
}
