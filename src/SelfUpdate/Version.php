<?php

declare(strict_types=1);

namespace Phpcq\Runner\SelfUpdate;

use Phpcq\RepositoryDefinition\VersionRequirementList;

final class Version
{
    private string $version;

    private string $pharFile;

    private ?string $signatureFile;

    private VersionRequirementList $requirements;

    public function __construct(
        string $version,
        VersionRequirementList $requirements,
        string $pharFile,
        ?string $signatureFile = null
    ) {
        $this->version       = $version;
        $this->requirements  = $requirements;
        $this->pharFile      = $pharFile;
        $this->signatureFile = $signatureFile;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getPharFile(): string
    {
        return $this->pharFile;
    }

    public function getSignatureFile(): ?string
    {
        return $this->signatureFile;
    }

    public function getRequirements(): VersionRequirementList
    {
        return $this->requirements;
    }
}
