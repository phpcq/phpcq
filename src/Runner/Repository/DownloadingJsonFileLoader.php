<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Phpcq\FileDownloader;
use Phpcq\RepositoryDefinition\JsonFileLoaderInterface;

use function dirname;

final class DownloadingJsonFileLoader implements JsonFileLoaderInterface
{
    /** @var FileDownloader */
    private $downloader;

    /** @var bool */
    private $force;

    public function __construct(FileDownloader $downloader, bool $force = false)
    {
        $this->downloader = $downloader;
        $this->force      = $force;
    }

    public function load(string $file, ?array $checksum = null): array
    {
        return $this->downloader->downloadJsonFile($file, dirname($file), $this->force, $checksum);
    }
}
