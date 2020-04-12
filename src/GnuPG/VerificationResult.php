<?php

declare(strict_types=1);

namespace Phpcq\GnuPG;

final class VerificationResult
{
    private $state;

    /** @var string|null */
    private $fingerprint;

    private function __construct(string $state, ?string $fingerprint = null)
    {
        $this->state = $state;
        $this->fingerprint = $fingerprint;
    }

    /** @SuppressWarnings(PHPMD.CamelCaseMethodName) */
    public static function UNKOWN_ERROR() : self
    {
        return new self('unknown');
    }

    /** @SuppressWarnings(PHPMD.CamelCaseMethodName) */
    public static function UNTRUSTED_KEY(?string $fingerprint) : self
    {
        return new self('untrusted_key', $fingerprint);
    }

    /** @SuppressWarnings(PHPMD.CamelCaseMethodName) */
    public static function VALID(?string $fingerprint): self
    {
        return new self('valid', $fingerprint);
    }

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function isValid() : bool
    {
        return $this->state === 'valid';
    }

    public function isUntrustedKey() : bool
    {
        return $this->state === 'untrusted_key';
    }
}
