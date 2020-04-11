<?php

declare(strict_types=1);

namespace Phpcq\GnuPG;

use Http\Message\RequestFactory;
use Http\Message\UriFactory;
use Phpcq\Exception\DownloadGpgKeyFailedException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\UriInterface;
use function http_build_query;

final class KeyDownloader
{
    const DEFAULT_KEYSERVERS = [
        'keys.openpgp.org',
        'keys.fedoraproject.org',
        'keyserver.ubuntu.com',
        'hkps.pool.sks-keyservers.net'
    ];

    /** @var ClientInterface */
    private $httpClient;

    /** @var RequestFactory */
    private $requestFactory;

    /** @var UriFactory */
    private $uriFactory;

    /** @var string[] */
    private $keyServers;

    /**
     * KeyDownloader constructor.
     *
     * @param ClientInterface $httpClient
     * @param RequestFactory  $requestFactory
     * @param UriFactory      $uriFactory
     * @param string[]        $keyServers
     */
    public function __construct(
        ClientInterface $httpClient,
        RequestFactory $requestFactory,
        UriFactory $uriFactory,
        ?array $keyServers = null
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->uriFactory = $uriFactory;
        $this->keyServers = $keyServers ?: self::DEFAULT_KEYSERVERS;
    }

    public function download(string $keyId) : string
    {
        foreach ($this->keyServers as $keyServer) {
            try {
                return $this->downloadFromServer($keyId, $keyServer);
            } catch (DownloadGpgKeyFailedException $exception) {
                // Try next keyserver
            }
        }

        throw DownloadGpgKeyFailedException::fromServers($keyId, $this->keyServers);
    }

    public function downloadFromServer(string $keyId, string $keyServer) : string
    {
        $requestUri = $this->createUri($keyServer, $keyServer);
        $request = $this->requestFactory->createRequest('GET', $requestUri);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            throw DownloadGpgKeyFailedException::fromServer($keyId, $keyServer, $exception);
        }

        // TODO: Should we verify if it's a valid key? Phive does it with an temporary import, see
        // https://github.com/phar-io/phive/blob/master/src/services/key/gpg/PublicKeyReader.php
        return $response->getBody()->getContents();
    }

    private function createUri(string $keyId, string $keyServer) : UriInterface
    {
        $params = [
            'op'      => 'get',
            'options' => 'mr',
            'search'  => '0x' . $keyId
        ];


        return $this->uriFactory->createUri($keyServer)
            ->withPath('/pks/lookup')
            ->withQuery(http_build_query($params));
    }
}
