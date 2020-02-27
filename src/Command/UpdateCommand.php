<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\ConfigLoader;
use Phpcq\FileDownloader;
use Phpcq\Repository\JsonRepositoryDumper;
use Phpcq\Repository\Repository;
use Phpcq\Repository\RepositoryFactory;
use Phpcq\Repository\ToolInformation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('update')->setDescription('Update the phpcq installation');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $phpcqPath = $input->getOption('tools');
        if (!is_dir($phpcqPath)) {
            mkdir($phpcqPath, 0777, true);
        }
        $cachePath = $input->getOption('cache');
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0777, true);
        }
        if ($output->isVeryVerbose()) {
            $output->writeln('Using HOME: ' . $phpcqPath);
            $output->writeln('Using CACHE: ' . $cachePath);
        }

        $configFile = $input->getOption('config');
        $config     = ConfigLoader::load($configFile);
        $downloader = new FileDownloader($cachePath, $config['auth'] ?? []);
        $factory    = new RepositoryFactory($downloader);
        // Download repositories
        $pool = $factory->buildPool($config);
        // Download needed tools and add to local repository.
        $installed = new Repository();
        foreach ($config['tools'] as $toolName => $tool) {
            $toolInfo = $pool->getTool($toolName, $tool['version']);
            // Download to destination path and add new information to installed repository.
            $pharName = sprintf('%1$s~%2$s.phar', $toolInfo->getName(), $toolInfo->getVersion());
            $downloader->downloadFileTo($toolInfo->getPharUrl(), $phpcqPath . '/' . $pharName);

            $localTool = new ToolInformation(
                $toolInfo->getName(),
                $toolInfo->getVersion(),
                $pharName,
                $toolInfo->getBootstrap()
            );

            $installed->addVersion($localTool);
        }
        // Save installed repository.
        $dumper = new JsonRepositoryDumper($phpcqPath);
        $dumper->dump($installed, 'installed.json');

        return 0;
    }
}
