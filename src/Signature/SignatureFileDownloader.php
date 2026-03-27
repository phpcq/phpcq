<?php

declare(strict_types=1);

namespace Phpcq\Runner\Signature;

use Phpcq\Runner\Downloader\DownloaderInterface;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\GnuPG\Downloader\FileDownloaderInterface;
use Phpcq\GnuPG\Exception\DownloadFailureException;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class SignatureFileDownloader implements FileDownloaderInterface
{
    public function __construct(
        private DownloaderInterface $fileDownloader,
        private OutputInterface $output
    ) {
    }

    #[\Override]
    public function downloadFile(string $url): string
    {
        try {
            $this->output->writeln('Downloading key from ' . $url, OutputInterface::VERBOSITY_VERBOSE);
            $buffer = $this->fileDownloader->downloadFile($url);
            $this->output->writeln('Key downloaded from ' . $url, OutputInterface::VERBOSITY_VERBOSE);
            return $buffer;
        } catch (RuntimeException $exception) {
            $this->output->writeln('Downloading key from ' . $url . ' failed', OutputInterface::VERBOSITY_VERBOSE);
            /** @psalm-suppress RedundantCast */
            throw new DownloadFailureException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }
}
