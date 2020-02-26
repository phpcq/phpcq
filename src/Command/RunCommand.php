<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\ConfigLoader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RunCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('run')->setDescription('Run configured build tasks');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFile  = $input->getArgument('config');
        $config      = ConfigLoader::load($configFile);

        // Download repositories
        // Tools laden, die benötigt werden
        // Passende Bootstraps laden
        // Initialisierung der Phars
        // Create build configuration
        // Execute task list

        return 0;
    }
}
