<?php

declare(strict_types=1);

namespace Phpcq\GnuPG;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use function file_get_contents;
use function file_put_contents;
use function uniqid;
use function unlink;

final class GnuGPBinaryVerifier implements VerifierInterface
{
    /** @var string */
    private $binaryPath;

    /** @var string */
    private $tempDirectory;

    /**
     * GnuGPBinaryVerifier constructor.
     *
     * @param string $binaryPath
     * @param string $tempDirectory
     */
    public function __construct(string $binaryPath, string $tempDirectory)
    {
        $this->binaryPath    = $binaryPath;
        $this->tempDirectory = $tempDirectory;
    }

    public function verify(string $messageFile, string $signatureFile) : bool
    {
        $messageFile = $this->createTemporaryFile($messageFile);
        $signatureFile = $this->createTemporaryFile($signatureFile);

        $command = [
            $this->binaryPath,
            '--verify',
            $signatureFile,
            $messageFile
        ];

        try {
            $process = new Process($command);
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            return false;
        } finally {
            unlink($messageFile);
            unlink($signatureFile);
        }

        return true;
    }

    private function createTemporaryFile(string $path): string
    {
        $tmpFile = $this->tempDirectory . '/' . uniqid('phpcq_gpg_', true);
        file_put_contents($tmpFile, file_get_contents($path));

        return $tmpFile;
    }
}
