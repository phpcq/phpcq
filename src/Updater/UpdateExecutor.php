<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater;

use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersionInterface;
use Phpcq\Runner\Downloader\DownloaderInterface;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\GnuPG\Signature\SignatureVerifier;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\RepositoryDefinition\AbstractHash;
use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Repository\InstalledRepositoryDumper;
use Phpcq\Runner\Repository\LockFileDumper;
use Symfony\Component\Filesystem\Filesystem;

use function assert;
use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function sprintf;

/**
 * @psalm-import-type TInstallPluginTask from \Phpcq\Runner\Updater\UpdateCalculator
 * @psalm-import-type TUpgradePluginTask from \Phpcq\Runner\Updater\UpdateCalculator
 * @psalm-import-type TKeepPluginTask from \Phpcq\Runner\Updater\UpdateCalculator
 * @psalm-import-type TRemovePluginTask from \Phpcq\Runner\Updater\UpdateCalculator
 * @psalm-import-type TPluginTask from \Phpcq\Runner\Updater\UpdateCalculator
 * @psalm-import-type TToolTask from \Phpcq\Runner\Updater\UpdateCalculator
 * @psalm-import-type TInstallToolTask from \Phpcq\Runner\Updater\UpdateCalculator
 * @psalm-import-type TUpgradeToolTask from \Phpcq\Runner\Updater\UpdateCalculator
 * @psalm-import-type TKeepToolTask from \Phpcq\Runner\Updater\UpdateCalculator
 * @psalm-import-type TRemoveToolTask from \Phpcq\Runner\Updater\UpdateCalculator
 */
final class UpdateExecutor
{
    /**
     * @var DownloaderInterface
     */
    private $downloader;

    /**
     * @var string
     */
    private $installedPluginPath;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var SignatureVerifier
     */
    private $verifier;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        DownloaderInterface $downloader,
        SignatureVerifier $verifier,
        string $pluginPath,
        OutputInterface $output
    ) {
        $this->downloader          = $downloader;
        $this->verifier            = $verifier;
        $this->installedPluginPath = $pluginPath;
        $this->output              = $output;
        $this->filesystem          = new Filesystem();
    }

    /** @psalm-param list<TPluginTask> $tasks */
    public function execute(array $tasks): void
    {
        $installed      = new InstalledRepository();
        $lockRepository = new InstalledRepository();

        foreach ($tasks as $task) {
            $desired = $task['version'];

            switch ($task['type']) {
                case 'keep':
                    /** @psalm-var TKeepPluginTask $task */
                    $plugin = new InstalledPlugin($task['plugin']->getPluginVersion());
                    $installed->addPlugin($plugin);
                    break;
                case 'install':
                    /** @psalm-var TInstallPluginTask $task */
                    $installed->addPlugin($this->installPlugin($desired, $task['signed']));
                    break;
                case 'upgrade':
                    /** @psalm-var TUpgradePluginTask $task */
                    $plugin = $this->upgradePlugin($desired, $task['old'], $task['signed']);
                    $installed->addPlugin($plugin);
                    break;
                case 'remove':
                    /** @psalm-var TRemovePluginTask $task */
                    $this->removePlugin($desired);
                    break;
            }

            if (isset($task['tasks'])) {
                $tools = $this->executePluginTasks($task['tasks'], $installed->getPlugin($desired->getName()));
                $lockRepository->addPlugin(
                    new InstalledPlugin($desired, $tools)
                );
            }
        }

        // Save installed repository.
        $filesystem = new Filesystem();
        $dumper = new InstalledRepositoryDumper($filesystem);
        $dumper->dump($installed, $this->installedPluginPath . '/installed.json');

        $dumper = new LockFileDumper($filesystem);
        $dumper->dump($lockRepository, getcwd() . '/.phpcq.lock');
    }

    private function installPlugin(PluginVersionInterface $plugin, bool $requireSigned): InstalledPlugin
    {
        $this->output->writeln(
            'Installing ' . $plugin->getName() . ' version ' . $plugin->getVersion(),
            OutputInterface::VERBOSITY_VERBOSE
        );

        return $this->installPluginVersion($plugin, $requireSigned);
    }

    private function upgradePlugin(
        PluginVersionInterface $plugin,
        PluginVersionInterface $old,
        bool $requireSigned
    ): InstalledPlugin {
        $this->output->writeln('Upgrading ' . $plugin->getName(), OutputInterface::VERBOSITY_VERBOSE);

        // Do not install new version before deleting old. Otherwise the reinstall of the same version will fail!
        $this->deletePluginFiles($old);

        return $this->installPluginVersion($plugin, $requireSigned);
    }

    private function removePlugin(PluginVersionInterface $pluginVersion): void
    {
        $this->output->writeln(
            'Removing ' . $pluginVersion->getName() . ' version ' . $pluginVersion->getVersion(),
            OutputInterface::VERBOSITY_VERBOSE
        );

        $this->filesystem->remove($this->installedPluginPath . '/' . $pluginVersion->getName());
    }

    private function installPluginVersion(PluginVersionInterface $plugin, bool $requireSigned): InstalledPlugin
    {
        assert($plugin instanceof PhpFilePluginVersion);
        $bootstrapFile = sprintf('%1$s/plugin.php', $plugin->getName());
        $bootstrapPath = $this->installedPluginPath . '/' . $bootstrapFile;
        $this->output->writeln('Downloading ' . $plugin->getFilePath(), OutputInterface::VERBOSITY_VERY_VERBOSE);

        $this->downloader->downloadFileTo($plugin->getFilePath(), $bootstrapPath);
        $this->validateHash($bootstrapPath, $plugin->getHash());
        $signatureName = $this->verifyPluginSignature($bootstrapPath, $plugin, $requireSigned);

        return new InstalledPlugin(
            new PhpFilePluginVersion(
                $plugin->getName(),
                $plugin->getVersion(),
                $plugin->getApiVersion(),
                $plugin->getRequirements(),
                $bootstrapFile,
                $signatureName,
                $plugin->getHash()
            )
        );
    }

    private function deletePluginFiles(PluginVersionInterface $pluginVersion): void
    {
        if ($pluginVersion instanceof PhpFilePluginVersionInterface) {
            $this->deleteFile($this->installedPluginPath . '/' . $pluginVersion->getFilePath());
        }

        if ($signatureUrl = $pluginVersion->getSignaturePath()) {
            $this->deleteFile($this->installedPluginPath . '/' . $signatureUrl);
        }
    }

    private function deleteFile(string $path): void
    {
        $this->output->writeln('Removing file ' . $path, OutputInterface::VERBOSITY_VERBOSE);
        $this->filesystem->remove($path);
    }

    private function validateHash(string $pathToPhar, ?AbstractHash $hash): void
    {
        if (null === $hash) {
            return;
        }

        if (!$hash->equals($hash::createForFile($pathToPhar, $hash->getType()))) {
            throw new RuntimeException('Invalid hash for file: ' . $pathToPhar);
        }
    }

    private function verifyPluginSignature(
        string $pharPath,
        PluginVersionInterface $pluginVersion,
        bool $requireSigned
    ): ?string {
        $signature = $pluginVersion->getSignaturePath();
        if (null === $signature) {
            if (! $requireSigned) {
                return null;
            }

            $this->deleteFile($pharPath);

            throw new RuntimeException(
                sprintf(
                    'Install tool "%s" rejected. No signature given. You may have to disable signature verification'
                    . ' for this tool',
                    $pluginVersion->getName(),
                )
            );
        }

        $signatureName = sprintf('%1$s~%2$s.asc', $pluginVersion->getName(), $pluginVersion->getVersion());
        $signaturePath = $this->installedPluginPath . '/' . $pluginVersion->getName() . '/' . $signatureName;
        file_put_contents($signaturePath, $signature);
        $result = $this->verifier->verify(file_get_contents($pharPath), $signature);

        if ($requireSigned && ! $result->isValid()) {
            $this->deleteFile($pharPath);
            $this->deleteFile($this->installedPluginPath . '/' . $signatureName);

            throw new RuntimeException(
                sprintf(
                    'Verify signature for tool "%s" failed with key fingerprint "%s"',
                    $pluginVersion->getName(),
                    $result->getFingerprint() ?: 'UNKNOWN'
                )
            );
        }

        return $signatureName;
    }

    /**
     * @psalm-param list<TToolTask> $tasks
     *
     * @return ToolVersionInterface[]
     *
     * @psalm-return list<ToolVersionInterface>
     */
    private function executePluginTasks(array $tasks, InstalledPlugin $plugin): array
    {
        $tools = [];
        foreach ($tasks as $task) {
            switch ($task['type']) {
                case 'keep':
                    /** @psalm-var TKeepToolTask $task */
                    $tools[] = $task['tool'];
                    $plugin->addTool($task['installed']);
                    break;
                case 'install':
                    /** @psalm-var TInstallToolTask $task */
                    $tools[] = $task['tool'];
                    $this->installTool($plugin, $task['tool'], $task['signed']);
                    break;
                case 'upgrade':
                    /** @psalm-var TUpgradeToolTask $task */
                    $tools[] = $task['tool'];
                    $this->upgradeTool($plugin, $task['tool'], $task['old'], $task['signed']);
                    break;
                case 'remove':
                    /** @psalm-var TRemoveToolTask $task */
                    $this->removeTool($task['tool']);
                    break;
            }
        }

        return $tools;
    }

    private function installTool(InstalledPlugin $plugin, ToolVersionInterface $tool, bool $requireSigned): void
    {
        $pharName      = null;
        $hash          = null;
        $signatureName = null;

        if ($pharUrl = $tool->getPharUrl()) {
            $pharName = sprintf('%1$s/tools/%2$s~%3$s.phar', $plugin->getName(), $tool->getName(), $tool->getVersion());
            $pharPath = $this->installedPluginPath . '/' . $pharName;
            $this->output->writeln('Downloading ' . $pharUrl, OutputInterface::VERBOSITY_VERY_VERBOSE);

            $this->downloader->downloadFileTo($pharUrl, $pharPath);
            $this->validateHash($pharPath, $tool->getHash());
            $signatureName = $this->verifyToolSignature($pharPath, $plugin, $tool, $requireSigned);
            $hash = $tool->getHash() ?: ToolHash::createForFile($pharPath);
        }

        $plugin->addTool(
            new ToolVersion(
                $tool->getName(),
                $tool->getVersion(),
                $pharName,
                clone $tool->getRequirements(),
                $hash,
                $signatureName
            )
        );
    }

    private function upgradeTool(
        InstalledPlugin $plugin,
        ToolVersionInterface $tool,
        ToolVersionInterface $old,
        bool $requireSigned
    ): void {
        $this->output->writeln('Upgrading', OutputInterface::VERBOSITY_VERBOSE);

        // Do not install new version before deleting old. Otherwise the reinstall of the same version will fail!
        $this->deleteToolVersion($old);

        $this->installTool($plugin, $tool, $requireSigned);
    }

    private function removeTool(ToolVersionInterface $toolVersion): void
    {
        $this->output->writeln(
            'Removing ' . $toolVersion->getName() . ' version ' . $toolVersion->getVersion(),
            OutputInterface::VERBOSITY_VERBOSE
        );

        $this->deleteToolVersion($toolVersion);
    }

    private function deleteToolVersion(ToolVersionInterface $old): void
    {
        if ($url = $old->getPharUrl()) {
            $this->deleteFile($url);
        }
        if ($url = $old->getSignatureUrl()) {
            $this->deleteFile($url);
        }
    }

    private function verifyToolSignature(
        string $pharPath,
        InstalledPlugin $plugin,
        ToolVersionInterface $tool,
        bool $requireSigned
    ): ?string {
        $signatureUrl = $tool->getSignatureUrl();
        if (null === $signatureUrl) {
            if (! $requireSigned) {
                return null;
            }

            $this->deleteFile($pharPath);

            throw new RuntimeException(
                sprintf(
                    'Install of tool "%s" for plugin "%s" rejected. No signature given. You may have to disable '
                    . 'signature verification for this tool',
                    $tool->getName(),
                    $plugin->getName()
                )
            );
        }

        $signatureName = sprintf('%1$s/tools/%2$s~%3$s.asc', $plugin->getName(), $tool->getName(), $tool->getVersion());
        $signaturePath = $this->installedPluginPath . '/' . $signatureName;
        $this->downloader->downloadFileTo($signatureUrl, $signaturePath);
        $result = $this->verifier->verify(file_get_contents($pharPath), file_get_contents($signaturePath));

        if ($requireSigned && ! $result->isValid()) {
            $this->deleteFile($pharPath);
            $this->deleteFile($this->installedPluginPath . '/' . $signatureName);

            throw new RuntimeException(
                sprintf(
                    'Install of tool "%s" for plugin "%s" rejected. No signature given. You may have to disable '
                    . 'signature verification for this tool',
                    $tool->getName(),
                    $plugin->getName()
                )
            );
        }

        return $signatureName;
    }
}
