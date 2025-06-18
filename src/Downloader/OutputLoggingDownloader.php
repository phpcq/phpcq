<?php

declare(strict_types=1);

namespace Phpcq\Runner\Downloader;

use Phpcq\PluginApi\Version10\Output\OutputInterface;

/** @psalm-import-type TOutputVerbosity from OutputInterface */
final class OutputLoggingDownloader implements DownloaderInterface
{
    /**
     * @param TOutputVerbosity $verbosity
     */
    public function __construct(
        private readonly DownloaderInterface $downloader,
        private readonly OutputInterface $output,
        private readonly int $verbosity = OutputInterface::VERBOSITY_DEBUG
    ) {
    }

    #[\Override]
    public function downloadFileTo(
        string $url,
        string $destinationFile,
        string $baseDir = '',
        bool $force = false
    ): void {
        $this->output->writeln('Downloading ' . $url . ' to ' . $destinationFile, $this->verbosity);
        $this->downloader->downloadFileTo($url, $destinationFile, $baseDir, $force);
    }

    #[\Override]
    public function downloadFile(string $url, string $baseDir = '', bool $force = false, ?array $hash = null): string
    {
        $this->output->writeln('Downloading ' . $url, $this->verbosity);

        return $this->downloader->downloadFile($url, $baseDir, $force, $hash);
    }

    #[\Override]
    public function downloadJsonFile(string $url, string $baseDir = '', bool $force = false, ?array $hash = null): array
    {
        $this->output->writeln('Downloading json file ' . $url, $this->verbosity);

        return $this->downloader->downloadJsonFile($url, $baseDir, $force, $hash);
    }
}
