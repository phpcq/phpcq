<?php

namespace Phpcq\Repository;

use Phpcq\Exception\RuntimeException;
use Phpcq\FileDownloader;

/**
 * Remote bootstrap loader.
 */
class RemoteBootstrap implements BootstrapInterface
{
    /**
     * @var FileDownloader
     */
    private $downloader;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $baseDir;

    public function __construct(string $version, string $url, FileDownloader $downloader, string $baseDir)
    {
        if ($version !== '1.0.0') {
            throw new RuntimeException('Invalid version string: ' . $version);
        }

        $this->url        = $url;
        $this->downloader = $downloader;
        $this->baseDir    = $baseDir;
    }

    public function getPluginVersion(): string
    {
        return '1.0.0';
    }

    public function getCode(): string
    {
        return $this->downloader->downloadFile($this->url, $this->baseDir);
    }
}
