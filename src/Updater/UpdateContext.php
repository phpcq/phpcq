<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater;

use Phpcq\GnuPG\Signature\SignatureVerifier;
use Phpcq\Runner\Downloader\DownloaderInterface;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Composer;
use Symfony\Component\Filesystem\Filesystem;

/** @psalm-immutable  */
final class UpdateContext
{
    public function __construct(
        public Filesystem $filesystem,
        public Composer $composer,
        public InstalledRepository $installedRepository,
        public InstalledRepository $lockRepository,
        public SignatureVerifier $signatureVerifier,
        public DownloaderInterface $downloader,
        public string $installedPluginPath
    ) {
    }
}
