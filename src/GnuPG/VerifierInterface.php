<?php

declare(strict_types=1);

namespace Phpcq\GnuPG;

interface VerifierInterface
{
    public function verify(string $messageFile, string $signatureFile): bool;
}
