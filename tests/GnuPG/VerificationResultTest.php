<?php

declare(strict_types=1);

namespace Phpcq\Test\GnuPG;

use Phpcq\GnuPG\VerificationResult;
use PHPUnit\Framework\TestCase;

final class VerificationResultTest extends TestCase
{
    public function testUnknownError() : void
    {
        $result = VerificationResult::UNKOWN_ERROR();

        $this->assertFalse($result->isUntrustedKey());
        $this->assertFalse($result->isValid());
        $this->assertNull($result->getFingerprint());
    }

    public function testUntrustedKey() : void
    {
        $fingerprint = 'FOO';
        $result = VerificationResult::UNTRUSTED_KEY($fingerprint);

        $this->assertTrue($result->isUntrustedKey());
        $this->assertFalse($result->isValid());
        $this->assertEquals($fingerprint, $result->getFingerprint());
    }

    public function testValid() : void
    {
        $fingerprint = 'FOO';
        $result = VerificationResult::VALID($fingerprint);

        $this->assertFalse($result->isUntrustedKey());
        $this->assertTrue($result->isValid());
        $this->assertEquals($fingerprint, $result->getFingerprint());
    }
}
