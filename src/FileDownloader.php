<?php

declare(strict_types=1);

namespace Phpcq;

use Phpcq\Exception\RuntimeException;

class FileDownloader
{
    /**
     * @var array
     */
    private $authConfig;

    /**
     * @var string
     */
    private $cacheDirectory;

    public function __construct(string $cacheDirectory, array $authConfig = [])
    {
        $this->cacheDirectory = $cacheDirectory;
        $this->authConfig     = $authConfig;
    }

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
    public function downloadFileTo(string $url, string $destinationFile, string $baseDir = '', bool $force = false): void
    {
        file_put_contents($destinationFile, $this->downloadFile($url, $baseDir, $force));
    }

    /**
     * Download a file and return it's content.
     *
     * @param string $url
     * @param string $baseDir
     * @param bool $force
     *
     * @return string
     */
    public function downloadFile(string $url, string $baseDir = '', bool $force = false): string
    {
        if (!is_dir($this->cacheDirectory)) {
            mkdir($this->cacheDirectory);
        }
        $cacheFile = $this->cacheDirectory . '/' . preg_replace('#[^a-zA-Z0-9]#', '-', $url);
        if ($force || !is_file($cacheFile)) {
            // FIXME: apply auth - download via any library like curl or guzzle or the like.
            file_put_contents($cacheFile, file_get_contents($this->validateUrlOrFile($url, $baseDir)));
        }

        return file_get_contents($cacheFile);
    }

    /**
     * Download a JSON file and return the decoded result.
     *
     * @param string $url
     * @param string $baseDir
     * @param bool $force
     *
     * @return array
     */
    public function downloadJsonFile(string $url, string $baseDir = '', bool $force = false): array
    {
        $data = json_decode($this->downloadFile($url, $baseDir, $force), true);
        if (null === $data) {
            throw new RuntimeException('Invalid repository ' . $url);
        }

        return $data;
    }

    private function validateUrlOrFile(string $url, string $baseDir): string
    {
        // Local absolute path?
        if (is_file($url)) {
            return $url;
        }
        // Local relative path?
        if ('' !== $baseDir && is_file($baseDir . '/' . $url)) {
            return $baseDir . '/' . $url;
        }
        // Perform URL check.
        $path         = parse_url($url, PHP_URL_PATH);
        $encoded_path = array_map('urlencode', explode('/', $path));
        $url          = str_replace($path, implode('/', $encoded_path), $url);
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        // Did not understand.
        throw new RuntimeException('Invalid URI passed: ' . $url);
    }
}