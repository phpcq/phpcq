<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Phpcq\Runner\Platform\PlatformInformation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PlatformInformationCommand extends Command
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('platform-information')->setDescription('Shows platform information');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $platformInformation = PlatformInformation::createFromCurrentPlatform();

        $table->setHeaders(['Name', 'Version']);
        $table->addRow(['PHP', $platformInformation->getPhpVersion()]);
        $table->addRow(new TableSeparator());

        foreach ($platformInformation->getExtensions() as $name => $version) {
            $table->addRow([$name, $version]);
        }

        $table->addRow(new TableSeparator());

        foreach ($platformInformation->getLibraries() as $name => $version) {
            $table->addRow([$name, $version]);
        }

        $table->render();

        return 0;
    }
}
