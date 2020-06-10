<?php

namespace Phpcq\Repository;

use Phpcq\Exception\RuntimeException;

/**
 * Locally installed bootstrap.
 */
class InstalledBootstrap implements BootstrapInterface
{
    /**
     * @var string
     */
    private $filePath;

    /**
     * @var BootstrapHash|null
     */
    private $hash;

    public function __construct(string $version, string $filePath, ?BootstrapHash $hash)
    {
        if ($version !== '1.0.0') {
            throw new RuntimeException('Invalid version string: ' . $version);
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException('File not found: ' . $filePath);
        }

        $this->filePath = $filePath;
        $this->hash     = $hash;
    }

    public function getPluginVersion(): string
    {
        return '1.0.0';
    }

    public function getCode(): string
    {
        return file_get_contents($this->filePath);
    }

    /**
     * Retrieve filePath.
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    public function getHash(): ?BootstrapHash
    {
        return $this->hash;
    }
}
