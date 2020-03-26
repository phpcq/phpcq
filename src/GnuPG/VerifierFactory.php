<?php

declare(strict_types=1);

namespace Phpcq\GnuPG;

use Phpcq\Exception\RuntimeException;
use function explode;
use function extension_loaded;
use function putenv;

final class VerifierFactory
{
    /** @var string */
    private $tempDirectory;

    public function __construct(string $tempDirectory)
    {
        $this->tempDirectory = $tempDirectory;
    }

    public function create(string $homeDir): VerifierInterface
    {
        if ($this->isGnugpExtensionLoaded()) {
            return $this->createExtensionVerifier($homeDir);
        }

        $gpgBinary = $this->findBinary();
        if ($gpgBinary === null) {
            throw new RuntimeException(sprintf('Neighter gnugp extension loaded nor gpg binary found'));
        }

        return $this->createBinaryVerifier($gpgBinary);
    }

    private function isGnugpExtensionLoaded(): bool
    {
        return extension_loaded('gnupg');

    }

    private function createExtensionVerifier(string $homeDir): VerifierInterface
    {
        putenv('GNUPGHOME=' . $homeDir);

        $gpg = new \Gnupg();
        $gpg->seterrormode(\Gnupg::ERROR_EXCEPTION);

        return new GnuGPExtensionVerifier($gpg);
    }

    private function createBinaryVerifier(string $gpgBinary): VerifierInterface
    {
        return new GnuGPBinaryVerifier($gpgBinary, $this->tempDirectory);
    }

    /** @SuppressWarnings(PHPMD.UnusedLocalVariable) */
    private function findBinary() : ?string
    {
        $which  = (stripos(PHP_OS, 'WIN') === 0) ? 'where.exe' : 'which';
        $result = exec(sprintf('%s %s', $which, 'gpg'), $output, $exitCode);

        if ($exitCode !== 0) {
            return null;
        }

        return explode("\n", $result)[0];
    }
}
