<?php

declare(strict_types=1);

namespace Phpcq\Runner\Downloader;

/**
 * @psalm-import-type TJsonRepository from \Phpcq\Runner\Repository\JsonRepositoryLoader
 * @psalm-type TRepositoryCheckSum = array{
 *   type: string,
 *   value: string,
 * } */
interface DownloaderInterface
{
    /**
     * Download a file to the given location.
     *
     * @param string $url
     * @param string $destinationFile
     * @param string $baseDir
     * @param bool $force
     *
     * @return void
     */
    public function downloadFileTo(
        string $url,
        string $destinationFile,
        string $baseDir = '',
        bool $force = false
    ): void;

    /**
     * Download a file and return it's content.
     *
     * @param string     $url
     * @param string     $baseDir
     * @param bool       $force
     * @param TRepositoryCheckSum|null $hash
     *
     * @return string
     */
    public function downloadFile(string $url, string $baseDir = '', bool $force = false, ?array $hash = null): string;

    /**
     * Download a JSON file and return the decoded result.
     *
     * @param string     $url
     * @param string     $baseDir
     * @param bool       $force
     * @param TRepositoryCheckSum|null $hash
     *
     * @return TJsonRepository
     */
    public function downloadJsonFile(
        string $url,
        string $baseDir = '',
        bool $force = false,
        ?array $hash = null
    ): array;
}
