<?php

declare(strict_types=1);

namespace Phpcq\Runner\Signature;

use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\Runner\FileDownloader;
use Phpcq\GnuPG\Downloader\FileDownloaderInterface;
use Phpcq\GnuPG\Exception\DownloadFailureException;
use Symfony\Component\Console\Output\OutputInterface;

final class SignatureFileDownloader implements FileDownloaderInterface
{
    /** @var FileDownloader */
    private $fileDownloader;

    /** @var OutputInterface */
    private $output;

    public function __construct(FileDownloader $fileDownloader, OutputInterface $output)
    {
        $this->fileDownloader = $fileDownloader;
        $this->output         = $output;
    }

    public function downloadFile(string $url): string
    {
        try {
            $this->output->writeln('Downloading key from ' . $url, OutputInterface::VERBOSITY_VERBOSE);
            $buffer = $this->fileDownloader->downloadFile($url);
            $this->output->writeln('Key downloaded from ' . $url, OutputInterface::VERBOSITY_VERBOSE);
            return $buffer;
        } catch (RuntimeException $exception) {
            $this->output->writeln('Downloading key from ' . $url . ' failed', OutputInterface::VERBOSITY_VERBOSE);
            throw new DownloadFailureException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }
}
