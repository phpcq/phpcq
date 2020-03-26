<?php

declare(strict_types=1);

namespace GnuGP;

use Phpcq\GnuPG\GnuGPBinaryVerifier;
use Phpcq\GnuPG\VerifierInterface;
use PHPUnit\Framework\TestCase;
use function dirname;
use function exec;
use function explode;
use function sprintf;
use function stripos;
use function sys_get_temp_dir;
use const PHP_OS;

final class GnuGPBinaryVerifierTest extends TestCase
{
    /** @var string|null */

    private $binary;
    protected function setUp() : void
    {
        $this->binary = $this->findBinary();

        if ($this->binary === null) {
            $this->markTestSkipped('GnuGP binary not found');
        }
    }

    public function testInstantiation(): void
    {
        if ($this->binary === null) {
            return;
        }

        $verifier = new GnuGPBinaryVerifier($this->binary, sys_get_temp_dir());
        $this->assertInstanceOf(VerifierInterface::class, $verifier);
    }

    public function testVerify(): void
    {
        if ($this->binary === null) {
            return;
        }

        $fixtures = dirname(__DIR__);
        $verifier = new GnuGPBinaryVerifier($this->binary, sys_get_temp_dir());
        $status   = $verifier->verify($fixtures . '/fixtures/gpg/test.txt', $fixtures . '/fixtures/gpg/test.txt.asc');

        $this->assertTrue($status);
    }

    public function testInvalidSignature(): void
    {
        if ($this->binary === null) {
            return;
        }

        $fixtures = dirname(__DIR__);
        $verifier = new GnuGPBinaryVerifier($this->binary, sys_get_temp_dir());
        $status   = $verifier->verify($fixtures . '/fixtures/gpg/test.txt', $fixtures . '/fixtures/gpg/test.txt.invalid.asc');

        $this->assertFalse($status);
    }

    /** @SuppressWarnings(PHPMD.UnusedLocalVariable) */
    private function findBinary() : ?string
    {
        $which  = (stripos(PHP_OS, 'WIN') === 0) ? 'where.exe' : 'which';
        $result = exec(sprintf('%s %s', $which, 'gpg'), $output, $exitCode);

        if ($exitCode !== 0) {

            return null;
        }

        return explode("\n", $result)[0];
    }
}
