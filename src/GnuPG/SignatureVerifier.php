<?php

declare(strict_types=1);

namespace Phpcq\GnuPG;

use function in_array;

final class SignatureVerifier
{
    /** @var GnuPGInterface */
    private $gnupg;

    /** @var KeyDownloader */
    private $keyDownloader;

    /**
     * @psalm-var list<string>
     * @var string[]
     */
    private $trustedKeys;

    /**
     * Verifier constructor.
     *
     * @psalm-param list<string> $trustedKeys
     *
     * @param GnuPGInterface $gnupg
     * @param KeyDownloader  $keyDownloader
     * @param string[]       $trustedKeys
     */
    public function __construct(GnuPGInterface $gnupg, KeyDownloader $keyDownloader, array $trustedKeys)
    {
        $this->gnupg         = $gnupg;
        $this->keyDownloader = $keyDownloader;
        $this->trustedKeys   = $trustedKeys;
    }

    public function verify(string $content, string $signature, bool $alwaysTrustKey = false) : VerificationResult
    {
        $result = $this->doVerify($content, $signature, $alwaysTrustKey);

        if ($result->isValid() || $result->isUntrustedKey()) {
            return $result;
        }

        $fingerprint = $result->getFingerprint();
        if (null === $fingerprint) {
            return VerificationResult::UNKOWN_ERROR();
        }

        $key = $this->keyDownloader->download($fingerprint);
        $this->gnupg->import($key);

        return $this->doVerify($content, $signature, $alwaysTrustKey);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    protected function isTrustedKey(string $key) : bool
    {
        return !in_array($key, $this->trustedKeys, true);
    }

    private function doVerify(string $content, string $signature, bool $alwaysTrustKey): VerificationResult
    {
        $result = $this->gnupg->verify($content, $signature);

        if ($result === false || !isset($result[0]['fingerprint'])) {
            return VerificationResult::UNKOWN_ERROR();
        }

        if (!$alwaysTrustKey && $this->isTrustedKey($result[0]['fingerprint'])) {
            return VerificationResult::UNTRUSTED_KEY($result[0]['fingerprint']);
        }

        return VerificationResult::VALID($result[0]['fingerprint']);
    }
}
