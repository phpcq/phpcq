<?php

declare(strict_types=1);

namespace GnuGP;

use Phpcq\GnuPG\GnuGPBinaryVerifier;
use Phpcq\GnuPG\GnuGPExtensionVerifier;
use Phpcq\GnuPG\VerifierInterface;
use PHPUnit\Framework\TestCase;
use function dirname;
use function extension_loaded;
use function sys_get_temp_dir;

final class GnuGPExtensionVerifierTest extends TestCase
{
    /** @var \Gnupg */
    private $gnugp;

    public function setUp() : void
    {
        if (!extension_loaded('gnupg')) {
            $this->markTestSkipped('Gnupg extension not loaded');

            return;
        }

        $this->gnugp = new \Gnupg();
    }

    public function testInstantiation(): void
    {
        if ($this->gnugp === null) {
            return;
        }

        $instance = new GnuGPExtensionVerifier($this->gnugp);
        $this->assertInstanceOf(VerifierInterface::class, $instance);
    }

    public function testVerify(): void
    {
        if ($this->gnugp === null) {
            return;
        }

        $fixtures = dirname(__DIR__);
        $verifier = new GnuGPExtensionVerifier($this->gnugp);
        $status   = $verifier->verify($fixtures . '/fixtures/gpg/test.txt.asc', $fixtures . '/fixtures/gpg/test.txt');

        $this->assertTrue($status);
    }

    public function testInvalidSignature(): void
    {
        if ($this->gnugp === null) {
            return;
        }

        $fixtures = dirname(__DIR__);
        $verifier = new GnuGPExtensionVerifier($this->gnugp);
        $status   = $verifier->verify($fixtures . '/fixtures/gpg/test.txt.invalid.asc', $fixtures . '/fixtures/gpg/test.txt');

        $this->assertFalse($status);
    }
}
