<?php

declare(strict_types=1);

namespace Phpcq\Exception;

use Throwable;
use function sprintf;

final class DownloadGpgKeyFailedException extends RuntimeException
{
    /**
     * @psalm-var list<string>
     * @var string[]
     */
    private $keyServers = [];

    /** @var string|null */
    private $keyId;

    /**
     * @psalm-param list<string> $keyServers
     * @param string[] $keyServers
     */
    public static function fromServers(string $keyId, array $keyServers) : self
    {
        $instance = new self(sprintf('Download gpg key "%s" from servers failed', $keyId));
        $instance->keyId = $keyId;
        $instance->keyServers = $keyServers;

        return $instance;
    }

    public static function fromServer(string $keyId, string $keyServer, Throwable $previous = null) : self
    {
        $instance = new self(
            sprintf('Download gpg key "%s" from server "%s" failed', $keyId, $keyServer), 0, $previous
        );

        $instance->keyId = $keyId;
        $instance->keyServers = [$keyServer];

        return $instance;
    }

    /**
     * @psalm-return list<string>
     * @return string[]
     */
    public function getKeyServers() : array
    {
        return $this->keyServers;
    }

    /**
     * Get the request gpg key id.
     *
     * @return string|null
     */
    public function getKeyId() : ?string
    {
        return $this->keyId;
    }
}
