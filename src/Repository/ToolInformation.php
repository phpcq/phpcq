<?php

declare(strict_types=1);

namespace Phpcq\Repository;

class ToolInformation implements ToolInformationInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $pharUrl;

    /**
     * @var BootstrapInterface
     */
    private $bootstrap;

    /**
     * @var array<string, string>
     */
    private $platformRequirements;

    /**
     * @var ToolHash|null
     */
    private $hash;

    /**
     * @var string|null
     */
    private $signatureUrl;

    /**
     * Create a new instance.
     *
     * @param string                $name
     * @param string                $version
     * @param string                $pharUrl
     * @param array<string, string> $platformRequirements
     * @param BootstrapInterface    $bootstrap
     * @param ToolHash|null         $hash
     * @param string|null           $signatureUrl
     */
    public function __construct(
        string $name,
        string $version,
        string $pharUrl,
        array $platformRequirements,
        BootstrapInterface $bootstrap,
        ?ToolHash $hash,
        ?string $signatureUrl
    ) {
        $this->name                 = $name;
        $this->version              = $version;
        $this->pharUrl              = $pharUrl;
        $this->platformRequirements = $platformRequirements;
        $this->bootstrap            = $bootstrap;
        $this->hash                 = $hash;
        $this->signatureUrl         = $signatureUrl;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getPharUrl(): string
    {
        return $this->pharUrl;
    }

    public function getBootstrap(): BootstrapInterface
    {
        return $this->bootstrap;
    }

    public function getPlatformRequirements(): array
    {
        return $this->platformRequirements;
    }

    public function getHash(): ?ToolHash
    {
        return $this->hash;
    }

    public function getSignatureUrl(): ?string
    {
        return $this->signatureUrl;
    }
}
