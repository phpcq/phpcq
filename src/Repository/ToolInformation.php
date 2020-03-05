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
     * @var array
     */
    private $platformRequirements;

    /**
     * Create a new instance.
     *
     * @param string $name
     * @param string $version
     * @param string $pharUrl
     * @param array $platformRequirements
     * @param BootstrapInterface $bootstrap
     */
    public function __construct(string $name, string $version, string $pharUrl, array $platformRequirements, BootstrapInterface $bootstrap)
    {
        $this->name                 = $name;
        $this->version              = $version;
        $this->pharUrl              = $pharUrl;
        $this->platformRequirements = $platformRequirements;
        $this->bootstrap            = $bootstrap;
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

    public function getPlatformRequirements() : array
    {
        return $this->platformRequirements;
    }
}