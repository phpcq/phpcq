<?php

declare(strict_types=1);

namespace Phpcq\Runner\SelfUpdate;

use Phpcq\RepositoryDefinition\VersionRequirementList;

final readonly class Version
{
    public function __construct(
        private string $version,
        private VersionRequirementList $requirements,
        private string $pharFile,
        private ?string $signatureFile = null
    ) {
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
