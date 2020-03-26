<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\ConfigLoader;
use Phpcq\FileDownloader;
use Phpcq\Platform\PlatformInformation;
use Phpcq\Repository\JsonRepositoryDumper;
use Phpcq\Repository\JsonRepositoryLoader;
use Phpcq\Repository\Repository;
use Phpcq\Repository\RepositoryFactory;
use Phpcq\Repository\ToolInformation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function assert;
use function is_string;

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
        assert(is_string($phpcqPath));
        $this->createDirectory($phpcqPath);

        $cachePath = $input->getOption('cache');
        assert(is_string($cachePath));
        $this->createDirectory($cachePath);

        if ($output->isVeryVerbose()) {
            $output->writeln('Using HOME: ' . $phpcqPath);
            $output->writeln('Using CACHE: ' . $cachePath);
        }

        $platformInformation = PlatformInformation::createFromCurrentPlatform();
        $configFile       = $input->getOption('config');
        assert(is_string($configFile));
        $config           = ConfigLoader::load($configFile);
        $downloader       = new FileDownloader($cachePath, $config['auth'] ?? []);
        $repositoryLoader = new JsonRepositoryLoader($platformInformation, $downloader, true);
        $factory          = new RepositoryFactory($repositoryLoader);
        // Download repositories
        $pool = $factory->buildPool($config['repositories'] ?? []);
        // Download needed tools and add to local repository.
        $installed = new Repository($platformInformation);
        foreach ($config['tools'] as $toolName => $tool) {
            $toolInfo = $pool->getTool($toolName, $tool['version']);
            // Download to destination path and add new information to installed repository.
            $pharName = sprintf('%1$s~%2$s.phar', $toolInfo->getName(), $toolInfo->getVersion());
            $downloader->downloadFileTo($toolInfo->getPharUrl(), $phpcqPath . '/' . $pharName);

            $localTool = new ToolInformation(
                $toolInfo->getName(),
                $toolInfo->getVersion(),
                $pharName,
                $toolInfo->getPlatformRequirements(),
                $toolInfo->getBootstrap(),
                $toolInfo->getHash(),
                $toolInfo->getSignatureUrl()
            );

            $installed->addVersion($localTool);
        }
        // Save installed repository.
        $dumper = new JsonRepositoryDumper($phpcqPath);
        $dumper->dump($installed, 'installed.json');

        return 0;
    }
}
