<?php

declare(strict_types=1);

namespace Phpcq\Runner\ToolUpdate;

use Phpcq\Exception\RuntimeException;
use Phpcq\FileDownloader;
use Phpcq\GnuPG\Signature\SignatureVerifier;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\Runner\Repository\LockFileDumper;
use Phpcq\RepositoryDefinition\AbstractHash;
use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Repository\InstalledRepositoryRepositoryDumper;
use Phpcq\Runner\Repository\Repository;
use Phpcq\Runner\Repository\RepositoryInterface;

use Symfony\Component\Filesystem\Filesystem;
use function assert;
use function file_get_contents;
use function sprintf;

/**
 * @psalm-import-type TUpdateTask from \Phpcq\ToolUpdate\UpdateCalculator
 */
final class UpdateExecutor
{
    /**
     * @var FileDownloader
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
     * @var RepositoryInterface|null
     */
    private $lockFileRepository;

    public function __construct(
        FileDownloader $downloader,
        SignatureVerifier $verifier,
        string $phpcqPath,
        OutputInterface $output,
        ?RepositoryInterface $lockFileRepository
    ) {
        $this->downloader          = $downloader;
        $this->verifier            = $verifier;
        $this->installedPluginPath = $phpcqPath;
        $this->output              = $output;
        $this->lockFileRepository  = $lockFileRepository;
    }

    /** @psalm-param list<TUpdateTask> $plugins */
    public function execute(array $plugins): void
    {
        $installed      = new InstalledRepository();
        $lockRepository = new Repository();

        foreach ($plugins as $task) {
            $pluginVersion = $task['version'];

            switch ($task['type']) {
                case 'keep':
                    $installed->addPlugin($task['plugin']);
                    if (null === $this->lockFileRepository) {
                        throw new RuntimeException('Unable to execute keep task without lock file repository');
                    }
                    $lockRepository->addPluginVersion(
                        $this->lockFileRepository->getPluginVersion($pluginVersion->getName(), $pluginVersion->getVersion())
                    );
                    break;
                case 'install':
                    /** @psalm-suppress PossiblyUndefinedArrayOffset - See https://github.com/vimeo/psalm/issues/3548 */
                    $installed->addPlugin($this->installPlugin($pluginVersion, $task['signed']));
                    $lockRepository->addPluginVersion($pluginVersion);
                    break;
                case 'upgrade':
                    /** @psalm-suppress PossiblyUndefinedArrayOffset - See https://github.com/vimeo/psalm/issues/3548 */
                    $installed->addPlugin($this->upgradePlugin($pluginVersion, $task['old'], $task['signed']));
                    $lockRepository->addPluginVersion($pluginVersion);
                    break;
                case 'remove':
                    $this->removeTool($pluginVersion);
                    break;
            }
        }

        // Save installed repository.
        $dumper = new InstalledRepositoryRepositoryDumper(new Filesystem());
        $dumper->dump($installed, $this->installedPluginPath . '/installed.json');

//        $lockDumper = new LockFileDumper(getcwd());
//        $lockDumper->dump($lockRepository, '.phpcq.lock');
        $this->lockFileRepository = $lockRepository;
    }

    private function installPlugin(PluginVersionInterface $plugin, bool $requireSigned): InstalledPlugin
    {
        $this->output->writeln(
            'Installing ' . $plugin->getName() . ' version ' . $plugin->getVersion(),
            OutputInterface::VERBOSITY_VERBOSE
        );

        return $this->installVersion($plugin, $requireSigned);
    }

    private function upgradePlugin(
        PluginVersionInterface $tool,
        PluginVersionInterface $old,
        bool $requireSigned
    ): InstalledPlugin {
        $this->output->writeln('Upgrading', OutputInterface::VERBOSITY_VERBOSE);

        // Do not install new version before deleting old. Otherwise the reinstall of the same version will fail!
        $this->deleteVersion($old);

        return $this->installVersion($tool, $requireSigned);
    }

    private function removeTool(PluginVersionInterface $tool): void
    {
        $this->output->writeln(
            'Removing ' . $tool->getName() . ' version ' . $tool->getVersion(),
            OutputInterface::VERBOSITY_VERBOSE
        );
        $this->deleteVersion($tool);
    }

    private function installVersion(PluginVersionInterface $plugin, bool $requireSigned): InstalledPlugin
    {
        assert($plugin instanceof PhpFilePluginVersion);
        $pharName = sprintf('%1$s/plugin.php', $plugin->getName());
        $pharPath = $this->installedPluginPath . '/' . $pharName;
        $this->output->writeln('Downloading ' . $plugin->getFilePath(), OutputInterface::VERBOSITY_VERY_VERBOSE);

        $this->downloader->downloadFileTo($plugin->getFilePath(), $pharPath);
        $this->validateHash($pharPath, $plugin->getHash());
        $signatureName = $this->verifySignature($pharPath, $plugin, $requireSigned);

        // FIXME: Install sub dependencies
        $tools = [];

        return new InstalledPlugin(
            new PhpFilePluginVersion(
                $plugin->getName(),
                $plugin->getVersion(),
                $plugin->getApiVersion(),
                $plugin->getRequirements(),
                $pharName,
                $signatureName,
                $plugin->getHash()
            ),
            $tools
        );
    }

    private function deleteVersion(PluginVersionInterface $pluginVersion): void
    {
        if ($pluginVersion instanceof PhpFilePluginVersion) {
            $this->deleteFile($this->installedPluginPath . '/' . $pluginVersion->getFilePath());
        }

        if ($signatureUrl = $pluginVersion->getSignature()) {
            $this->deleteFile($this->installedPluginPath . '/' . $signatureUrl);
        }
    }

    private function deleteFile(string $path): void
    {
        $this->output->writeln('Removing file ' . $path, OutputInterface::VERBOSITY_VERBOSE);
        if (!unlink($path)) {
            throw new RuntimeException('Could not remove file: ' . $path);
        }
    }

    private function validateHash(string $pathToPhar, ?AbstractHash $hash): void
    {
        if (null === $hash) {
            return;
        }

        if (! $hash->equals($hash::createForFile($pathToPhar, $hash->getType()))) {
            throw new RuntimeException('Invalid hash for file: ' . $pathToPhar);
        }
    }

    private function verifySignature(string $pharPath, PluginVersionInterface $pluginVersion, bool $requireSigned): ?string
    {
        $signatureUrl = $pluginVersion->getSignature();
        if (null === $signatureUrl) {
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
        $signaturePath = $this->installedPluginPath . '/' . $signatureName;
        $this->downloader->downloadFileTo($signatureUrl, $signaturePath);
        $result = $this->verifier->verify(file_get_contents($pharPath), file_get_contents($signaturePath));

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
}
