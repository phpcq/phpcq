<?php

namespace Phpcq\Repository;

/**
 * Describes a build tool.
 */
interface ToolInformationInterface
{
    /**
     * Obtain the name of this tool.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Obtain the version string of this tool.
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Obtain the phar download URL.
     *
     * @return string
     */
    public function getPharUrl(): string;

    /**
     * Obtain the bootstrap information.
     *
     * @return BootstrapInterface
     */
    public function getBootstrap(): BootstrapInterface;

    /**
     * Obtain the platform requirements
     *
     * @return string[]
     */
    public function getPlatformRequirements() : array;

    /**
     * Obtain the hash for the tool (if any).
     *
     * @return ToolHash|null
     */
    public function getHash(): ?ToolHash;

    /**
     * Obtain the signature URL (if any).
     *
     * @return string|null
     */
    public function getSignatureUrl(): ?string;
}
