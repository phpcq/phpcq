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
    /** @var Filesystem */
    public $filesystem;

    /** @var Composer */
    public $composer;

    /** @var InstalledRepository */
    public $installedRepository;

    /** @var InstalledRepository */
    public $lockRepository;

    /** @var SignatureVerifier */
    public $signatureVerifier;

    /** @var DownloaderInterface */
    public $downloader;

    /** @var string */
    public $installedPluginPath;

    public function __construct(
        Filesystem $filesystem,
        Composer $composer,
        InstalledRepository $installedRepository,
        InstalledRepository $lockRepository,
        SignatureVerifier $signatureVerifier,
        DownloaderInterface $downloader,
        string $installedPluginPath
    ) {
        $this->filesystem          = $filesystem;
        $this->composer            = $composer;
        $this->installedRepository = $installedRepository;
        $this->lockRepository      = $lockRepository;
        $this->signatureVerifier   = $signatureVerifier;
        $this->downloader          = $downloader;
        $this->installedPluginPath = $installedPluginPath;
    }
}
