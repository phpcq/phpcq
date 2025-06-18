<?php

declare(strict_types=1);

namespace Phpcq\Runner\Downloader;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Phpcq\Runner\Exception\InvalidHashException;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\RepositoryDefinition\AbstractHash;

use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_file;
use function mkdir;
use function strpos;

/**
 * @psalm-import-type TJsonRepository from \Phpcq\Runner\Repository\JsonRepositoryLoader
 * @psalm-type TRepositoryCheckSum = array{
 *   type: string,
 *   value: string,
 * } */
class FileDownloader implements DownloaderInterface
{
    public function __construct(private readonly string $cacheDirectory, private readonly array $authConfig = [])
    {
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
    #[\Override]
    public function downloadFileTo(
        string $url,
        string $destinationFile,
        string $baseDir = '',
        bool $force = false
    ): void {
        $directory = dirname($destinationFile);
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($destinationFile, $this->downloadFile($url, $baseDir, $force));
    }

    /**
     * Download a file and return it's content.
     *
     * @param string                   $url
     * @param string                   $baseDir
     * @param bool                     $force
     * @param TRepositoryCheckSum|null $hash
     *
     * @return string
     */
    #[\Override]
    public function downloadFile(string $url, string $baseDir = '', bool $force = false, ?array $hash = null): string
    {
        if (!is_dir($this->cacheDirectory)) {
            mkdir($this->cacheDirectory);
        }
        $cacheFile = $this->cacheDirectory . '/' . ((string) preg_replace('#[^a-zA-Z0-9]#', '-', $url));
        if ($force || !is_file($cacheFile) || !$this->cacheFileMatches($cacheFile, $hash)) {
            $url = $this->validateUrlOrFile($url, $baseDir);

            if (is_file($url)) {
                file_put_contents($cacheFile, (string) file_get_contents($url));
            } else {
                $client = $this->getClient($url);
                // FIXME: apply auth.
                try {
                    $response = $client->request('GET', $url);
                } catch (RequestException $exception) {
                    throw new RuntimeException('Failed to download: ' . $url, $exception->getCode(), $exception);
                }

                if (200 !== $response->getStatusCode()) {
                    throw new RuntimeException('Failed to download: ' . $url);
                }

                file_put_contents($cacheFile, $response->getBody()->getContents());
            }
        }

        return (string) file_get_contents($cacheFile);
    }

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
    #[\Override]
    public function downloadJsonFile(string $url, string $baseDir = '', bool $force = false, ?array $hash = null): array
    {
        /**
         * @var null|array $data
         * @psalm-var ?TJsonRepository $data
         */
        $data = json_decode($this->downloadFile($url, $baseDir, $force, $hash), true);
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
        $path        = (string) parse_url($url, PHP_URL_PATH);
        $encodedPath = array_map('urlencode', explode('/', $path));
        $newUrl      = str_replace($path, implode('/', $encodedPath), $url);
        if (filter_var($newUrl, FILTER_VALIDATE_URL)) {
            return $newUrl;
        }
        $newUrl = $baseDir . '/' . $newUrl;
        if (filter_var($newUrl, FILTER_VALIDATE_URL)) {
            return $newUrl;
        }

        // Did not understand.
        throw new RuntimeException('Invalid URI passed: ' . $url);
    }

    /**
     * Check the hash for the passed cache file - return true if it is valid, false otherwise.
     *
     * @param string     $cacheFile The file to check
     * @param TRepositoryCheckSum|null $hash The hash to validate.
     *
     * @return bool
     */
    private function cacheFileMatches(string $cacheFile, ?array $hash): bool
    {
        if (null === $hash) {
            return false;
        }

        /**
         * @psalm-var array<AbstractHash::SHA_1|AbstractHash::SHA_256|AbstractHash::SHA_384|AbstractHash::SHA_512,
         * string> $hashMap
         */
        static $hashMap = [
            AbstractHash::SHA_1   => 'sha1',
            AbstractHash::SHA_256 => 'sha256',
            AbstractHash::SHA_384 => 'sha384',
            AbstractHash::SHA_512 => 'sha512',
        ];

        if (!isset($hashMap[$hash['type']])) {
            throw new InvalidHashException($hash['type'], $hash['value']);
        }

        return $hash['value'] === hash_file($hashMap[$hash['type']], $cacheFile);
    }

    private function getClient(string $url): Client
    {
        $options = [];
        if (!is_file($url) && str_starts_with($url, 'https://hkps.pool.sks-keyservers.net')) {
            $options['verify'] = __DIR__ . '/Resources/certs/sks-keyservers.netCA.pem';
        }

        // FIXME: Move cache layer here.
        return new Client($options);
    }
}
