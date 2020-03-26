<?php

declare(strict_types=1);

namespace Phpcq\GnuPG;

use Gnupg;

final class GnuGPExtensionVerifier implements VerifierInterface
{
    /** @var Gnupg */
    private $gpg;

    /**
     * GnuGPBinaryVerifier constructor.
     *
     * @param Gnupg $gpg
     */
    public function __construct(Gnupg $gpg)
    {
        $this->gpg = $gpg;
    }

    public function verify(string $messageFile, string $signatureFile) : bool
    {
        return $this->gpg->verify($messageFile, $signatureFile) !== false;
    }
}
