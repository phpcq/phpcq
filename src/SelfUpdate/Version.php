<?php

declare(strict_types=1);

namespace Phpcq\Runner\SelfUpdate;

use Phpcq\RepositoryDefinition\VersionRequirementList;

final class Version
{
    public function __construct(
        private readonly string $version,
        private readonly VersionRequirementList $requirements,
        private readonly string $pharFile,
        private readonly ?string $signatureFile = null
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
