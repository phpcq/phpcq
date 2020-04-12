<?php

declare(strict_types=1);

namespace Phpcq\GnuPG;

use Phpcq\Exception\DownloadGpgKeyFailedException;
use Phpcq\Exception\RuntimeException;
use Phpcq\FileDownloader;

final class KeyDownloader
{
    const DEFAULT_KEYSERVERS = [
        'keys.openpgp.org',
        'keys.fedoraproject.org',
        'keyserver.ubuntu.com',
        'hkps.pool.sks-keyservers.net'
    ];

    /** @var string[] */
    private $keyServers;

    /**
     * @var FileDownloader
     */
    private $fileDownloader;

    public function __construct(
        FileDownloader $fileDownloader,
        ?array $keyServers = null
    ) {
        $this->fileDownloader = $fileDownloader;
        $this->keyServers = $keyServers ?: self::DEFAULT_KEYSERVERS;
    }

    public function download(string $keyId) : string
    {
        foreach ($this->keyServers as $keyServer) {
            try {
                return $this->fileDownloader->downloadFile($this->createUri($keyId, $keyServer));
            } catch (RuntimeException $exception) {
                // Try next keyserver
            }
        }

        throw DownloadGpgKeyFailedException::fromServers($keyId, $this->keyServers);
    }

    private function createUri(string $keyId, string $keyServer) : string
    {
        return sprintf('https://%s/pks/lookup?op=get&options=mr&search=0x%s', $keyServer, $keyId);
    }
}
