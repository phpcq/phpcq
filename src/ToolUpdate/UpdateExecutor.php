<?php

declare(strict_types=1);

namespace Phpcq\ToolUpdate;

use Phpcq\Exception\RuntimeException;
use Phpcq\FileDownloader;
use Phpcq\GnuPG\Signature\SignatureVerifier;
use Phpcq\PluginApi\Version10\OutputInterface;
use Phpcq\Repository\InstalledBootstrap;
use Phpcq\Repository\JsonRepositoryDumper;
use Phpcq\Repository\LockFileDumper;
use Phpcq\Repository\Repository;
use Phpcq\Repository\RepositoryInterface;
use Phpcq\Repository\ToolHash;
use Phpcq\Repository\ToolInformation;
use Phpcq\Repository\ToolInformationInterface;

use function file_get_contents;
use function sprintf;

/**
 * @psalm-import-type TUpdateTask from \Phpcq\ToolUpdate\UpdateCalculator
 * @psalm-import-type TInstallTask from \Phpcq\ToolUpdate\UpdateCalculator
 */
final class UpdateExecutor
{
    public const TRUST_SIGNED = 'signed';
    public const TRUST_UNSIGNED = 'unsigned';
    public const TRUST_UNKNOWN_KEY = 'unknown-key';

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
        $this->downloader         = $downloader;
        $this->verifier           = $verifier;
        $this->phpcqPath          = $phpcqPath;
        $this->output             = $output;
        $this->lockFileRepository = $lockFileRepository;
    }

    /** @psalm-param list<TUpdateTask> $tasks */
    public function execute(array $tasks): void
    {
        $installed      = new Repository();
        $lockRepository = new Repository();

        foreach ($tasks as $task) {
            $tool = $task['tool'];

            switch ($task['type']) {
                case 'keep':
                    $installed->addVersion($tool);
                    if (null === $this->lockFileRepository) {
                        throw new RuntimeException('Unable to execute keep task without lock file repository');
                    }
                    $lockRepository->addVersion(
                        $this->lockFileRepository->getTool($tool->getName(), $tool->getVersion())
                    );
                    break;
                case 'install':
                    /** @psalm-suppress PossiblyUndefinedArrayOffset - See https://github.com/vimeo/psalm/issues/3548 */
                    $installed->addVersion($this->installTool($tool, $task['signed']));
                    $lockRepository->addVersion($tool);
                    break;
                case 'upgrade':
                    /** @psalm-suppress PossiblyUndefinedArrayOffset - See https://github.com/vimeo/psalm/issues/3548 */
                    $installed->addVersion($this->upgradeTool($tool, $task['old'], $task['signed']));
                    $lockRepository->addVersion($tool);
                    break;
                case 'remove':
                    $this->removeTool($tool);
                    break;
            }
        }

        // Save installed repository.
        $dumper = new JsonRepositoryDumper($this->phpcqPath);
        $dumper->dump($installed, 'installed.json');

        $lockDumper = new LockFileDumper(getcwd());
        $lockDumper->dump($lockRepository, '.phpcq.lock');
        $this->lockFileRepository = $lockRepository;
    }

    private function installTool(ToolInformationInterface $tool, bool $requireSigned): ToolInformationInterface
    {
        $this->output->writeln(
            'Installing ' . $tool->getName() . ' version ' . $tool->getVersion(),
            OutputInterface::VERBOSITY_VERBOSE
        );

        return $this->installVersion($tool, $requireSigned);
    }

    private function upgradeTool(
        ToolInformationInterface $tool,
        ToolInformationInterface $old,
        bool $requireSigned
    ): ToolInformationInterface {
        $this->output->writeln('Upgrading', OutputInterface::VERBOSITY_VERBOSE);

        // Do not install new version before deleting old. Otherwise the reinstall of the same version will fail!
        $this->deleteVersion($old);

        return $this->installVersion($tool, $requireSigned);
    }

    private function removeTool(ToolInformationInterface $tool): void
    {
        $this->output->writeln(
            'Removing ' . $tool->getName() . ' version ' . $tool->getVersion(),
            OutputInterface::VERBOSITY_VERBOSE
        );
        $this->deleteVersion($tool);
    }

    private function installVersion(ToolInformationInterface $tool, bool $requireSigned): ToolInformationInterface
    {
        $pharName = sprintf('%1$s~%2$s.phar', $tool->getName(), $tool->getVersion());
        $pharPath = $this->phpcqPath . '/' . $pharName;
        $this->output->writeln('Downloading ' . $tool->getPharUrl(), OutputInterface::VERBOSITY_VERY_VERBOSE);

        $this->downloader->downloadFileTo($tool->getPharUrl(), $pharPath);
        $this->validateHash($pharPath, $tool->getHash());
        $signatureName = $this->verifySignature($pharPath, $tool, $requireSigned);

        return new ToolInformation(
            $tool->getName(),
            $tool->getVersion(),
            $pharName,
            $tool->getPlatformRequirements(),
            $tool->getBootstrap(),
            $tool->getHash(),
            $signatureName
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

        if ($signatureUrl = $tool->getSignatureUrl()) {
            $this->deleteFile($this->phpcqPath . '/' . $signatureUrl);
        }
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

        /** @psalm-var array<string,string> $hashMap */
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

    private function verifySignature(string $pharPath, ToolInformationInterface $tool, bool $requireSigned): ?string
    {
        $signatureUrl = $tool->getSignatureUrl();
        if (null === $signatureUrl) {
            if (! $requireSigned) {
                return null;
            }

            $this->deleteFile($pharPath);

            throw new RuntimeException(
                sprintf(
                    'Install tool "%s" rejected. No signature given. You may have to disable signature verification'
                    . ' for this tool',
                    $tool->getName(),
                )
            );
        }

        $signatureName = sprintf('%1$s~%2$s.asc', $tool->getName(), $tool->getVersion());
        $signaturePath = $this->phpcqPath . '/' . $signatureName;
        $this->downloader->downloadFileTo($signatureUrl, $signaturePath);
        $result = $this->verifier->verify(file_get_contents($pharPath), file_get_contents($signaturePath));

        if ($requireSigned && ! $result->isValid()) {
            $this->deleteFile($pharPath);
            $this->deleteFile($this->phpcqPath . '/' . $signatureName);

            throw new RuntimeException(
                sprintf(
                    'Verify signature for tool "%s" failed with key fingerprint "%s"',
                    $tool->getName(),
                    $result->getFingerprint() ?: 'UNKNOWN'
                )
            );
        }

        return $signatureName;
    }
}
