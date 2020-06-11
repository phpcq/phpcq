<?php

declare(strict_types=1);

namespace Phpcq;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Phpcq\Exception\InvalidHashException;
use Phpcq\Exception\RuntimeException;
use Phpcq\Repository\ToolHash;

use function file_get_contents;
use function file_put_contents;
use function is_file;
use function strpos;

/**
 * @psalm-import-type TJsonRepository from \Phpcq\Repository\JsonRepositoryLoader
 * @psalm-import-type TToolHash from \Phpcq\Repository\ToolHash
 */
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
    public function downloadFileTo(
        string $url,
        string $destinationFile,
        string $baseDir = '',
        bool $force = false
    ): void {
        file_put_contents($destinationFile, $this->downloadFile($url, $baseDir, $force));
    }

    /**
     * Download a file and return it's content.
     *
     * @param string     $url
     * @param string     $baseDir
     * @param bool       $force
     * @param array|null $hash
     *
     * @psalm-param ?TToolHash $hash
     *
     * @return string
     */
    public function downloadFile(string $url, string $baseDir = '', bool $force = false, ?array $hash = null): string
    {
        if (!is_dir($this->cacheDirectory)) {
            mkdir($this->cacheDirectory);
        }
        $cacheFile = $this->cacheDirectory . '/' . preg_replace('#[^a-zA-Z0-9]#', '-', $url);
        if ($force || !is_file($cacheFile) || !$this->cacheFileMatches($cacheFile, $hash)) {
            $url = $this->validateUrlOrFile($url, $baseDir);

            if (is_file($url)) {
                file_put_contents($cacheFile, file_get_contents($url));
            } else {
                $client = $this->getClient($url);
                // FIXME: apply auth.
                try {
                    $response = $client->request('GET', $url);
                } catch (RequestException $exception) {
                    throw new RuntimeException('Failed to download: ' . $url, (int) $exception->getCode(), $exception);
                }

                if (200 !== $response->getStatusCode()) {
                    throw new RuntimeException('Failed to download: ' . $url);
                }

                file_put_contents($cacheFile, $response->getBody());
            }
        }

        return file_get_contents($cacheFile);
    }

    /**
     * Download a JSON file and return the decoded result.
     *
     * @param string     $url
     * @param string     $baseDir
     * @param bool       $force
     * @param array|null $hash
     *
     * @psalm-param ?TToolHash $hash
     *
     * @return array
     * @psalm-return TJsonRepository
     */
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
        $path        = parse_url($url, PHP_URL_PATH);
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
     * @param array|null $hash      he hash to validate.
     *
     * @psalm-param ?TToolHash $hash
     *
     * @return bool
     */
    private function cacheFileMatches(string $cacheFile, ?array $hash): bool
    {
        if (null === $hash) {
            return false;
        }

        /** @psalm-var array<ToolHash::SHA_1|ToolHash::SHA_256|ToolHash::SHA_384|ToolHash::SHA_512, string> $hashMap */
        static $hashMap = [
            ToolHash::SHA_1   => 'sha1',
            ToolHash::SHA_256 => 'sha256',
            ToolHash::SHA_384 => 'sha384',
            ToolHash::SHA_512 => 'sha512',
        ];

        if (!isset($hashMap[$hash['type']])) {
            throw new InvalidHashException($hash['type'], $hash['value']);
        }

        return $hash['value'] === hash_file($hashMap[$hash['type']], $cacheFile);
    }

    private function getClient(string $url): Client
    {
        $options = [];
        if (!is_file($url) && strpos($url, 'https://hkps.pool.sks-keyservers.net') === 0) {
            $options['verify'] = __DIR__ . '/Resources/certs/sks-keyservers.netCA.pem';
        }

        // FIXME: Move cache layer here.
        return new Client($options);
    }
}
