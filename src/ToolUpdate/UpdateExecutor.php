<?php

declare(strict_types=1);

namespace Phpcq\ToolUpdate;

use Phpcq\Exception\RuntimeException;
use Phpcq\FileDownloader;
use Phpcq\Platform\PlatformInformationInterface;
use Phpcq\PluginApi\Version10\OutputInterface;
use Phpcq\Repository\InstalledBootstrap;
use Phpcq\Repository\JsonRepositoryDumper;
use Phpcq\Repository\Repository;
use Phpcq\Repository\ToolHash;
use Phpcq\Repository\ToolInformation;
use Phpcq\Repository\ToolInformationInterface;

final class UpdateExecutor
{
    /**
     * @var FileDownloader
     */
    private $downloader;

    /**
     * @var string
     */
    private $phpcqPath;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var PlatformInformationInterface
     */
    private $platform;

    public function __construct(
        PlatformInformationInterface $platform,
        FileDownloader $downloader,
        string $phpcqPath,
        OutputInterface $output
    )
    {
        $this->platform   = $platform;
        $this->downloader = $downloader;
        $this->phpcqPath  = $phpcqPath;
        $this->output     = $output;
    }

    public function execute(array $tasks): void
    {
        $installed = new Repository($this->platform);
        foreach ($tasks as $task) {
            switch ($task['type']) {
                case 'keep':
                    $installed->addVersion($task['tool']);
                    break;
                case 'install':
                    $installed->addVersion($this->installTool($task['tool']));
                    break;
                case 'upgrade':
                    $installed->addVersion($this->upgradeTool($task['tool'], $task['old']));
                    break;
                case 'remove':
                    $this->removeTool($task['tool']);
                    break;
            }
        }

        // Save installed repository.
        $dumper = new JsonRepositoryDumper($this->phpcqPath);
        $dumper->dump($installed, 'installed.json');
    }

    private function installTool(ToolInformationInterface $tool): ToolInformationInterface
    {
        $this->output->writeln('Installing', OutputInterface::VERBOSITY_VERBOSE);

        return $this->installVersion($tool);
    }

    private function upgradeTool(ToolInformationInterface $tool, ToolInformationInterface $old): ToolInformationInterface
    {
        $this->output->writeln('Upgrading', OutputInterface::VERBOSITY_VERBOSE);

        $new = $this->installVersion($tool);
        $this->deleteVersion($old);

        return $new;
    }

    private function removeTool(ToolInformationInterface $tool): void
    {
        $this->output->writeln('Removing ' . $tool->getName() . ' version ' . $tool->getVersion(), OutputInterface::VERBOSITY_VERBOSE);
        $this->deleteVersion($tool);
    }

    private function installVersion(ToolInformationInterface $tool): ToolInformationInterface
    {
        $pharName = sprintf('%1$s~%2$s.phar', $tool->getName(), $tool->getVersion());
        $pharPath = $this->phpcqPath . '/' . $pharName;
        $this->output->writeln('Downloading ' . $tool->getPharUrl(), OutputInterface::VERBOSITY_VERY_VERBOSE);

        $this->downloader->downloadFileTo($tool->getPharUrl(), $pharPath);
        $this->validateHash($pharPath, $tool->getHash());

        return new ToolInformation(
            $tool->getName(),
            $tool->getVersion(),
            $pharName,
            $tool->getPlatformRequirements(),
            $tool->getBootstrap(),
            $tool->getHash(),
            $tool->getSignatureUrl()
        );
    }

    private function deleteVersion(ToolInformationInterface $tool): void
    {
        $this->deleteFile($this->phpcqPath . '/' . $tool->getPharUrl());
        $bootstrap = $tool->getBootstrap();
        if (!$bootstrap instanceof InstalledBootstrap) {
            throw new RuntimeException('Can only remove installed bootstrap files.');
        }

        $this->deleteFile($bootstrap->getFilePath());
    }

    private function deleteFile(string $path): void
    {
        $this->output->writeln('Removing file ' . $path, OutputInterface::VERBOSITY_VERBOSE);
        if (!unlink($path)) {
            throw new RuntimeException('Could not remove file: ' . $path);
        }
    }

    private function validateHash(string $pathToPhar, ?ToolHash $hash): void
    {
        if (null === $hash) {
            return;
        }

        static $hashMap = [
            ToolHash::SHA_1   => 'sha1',
            ToolHash::SHA_256 => 'sha256',
            ToolHash::SHA_384 => 'sha384',
            ToolHash::SHA_512 => 'sha512',
        ];

        if ($hash->getValue() !== hash_file($hashMap[$hash->getType()], $pathToPhar)) {
            throw new RuntimeException('Invalid hash for file: ' . $pathToPhar);
        }
    }
}