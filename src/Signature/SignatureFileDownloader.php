<?php

declare(strict_types=1);

namespace Phpcq\Signature;

use Phpcq\Exception\RuntimeException;
use Phpcq\FileDownloader;
use Phpcq\GnuPG\Downloader\FileDownloaderInterface;
use Phpcq\GnuPG\Exception\DownloadFailureException;

final class SignatureFileDownloader implements FileDownloaderInterface
{
    /** @var FileDownloader */
    private $fileDownloader;

    public function __construct(FileDownloader $fileDownloader)
    {
        $this->fileDownloader = $fileDownloader;
    }

    public function downloadFile(string $url) : string
    {
        try {
            return $this->fileDownloader->downloadFile($url);
        } catch (RuntimeException $exception) {
            throw new DownloadFailureException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }
}
