<?php

declare(strict_types=1);

namespace Phpcq\Test\GnuPG;

use Phpcq\FileDownloader;
use Phpcq\GnuPG\GnuPGInterface;
use Phpcq\GnuPG\KeyDownloader;
use Phpcq\GnuPG\SignatureVerifier;
use PHPUnit\Framework\TestCase;
use function sys_get_temp_dir;

final class SignatureVerifierTest extends TestCase
{
    public function testVerifyWithKnownAndTrustedKey(): void
    {
        $gnupg = $this->createMock(GnuPGInterface::class);
        $gnupg
            ->expects($this->once())
            ->method('verify')
            ->withAnyParameters()
            ->willReturn([['fingerprint' => 'ABCDEF']]);

        $downloader = $this
            ->getMockBuilder(FileDownloader::class)
            ->onlyMethods(['downloadFile'])
            ->setConstructorArgs([sys_get_temp_dir() . '/phpcq', []])
            ->getMock();

        $verifier = new SignatureVerifier($gnupg, new KeyDownloader($downloader), ['ABCDEF']);
        $result   = $verifier->verify('foo', 'bar');

        $this->assertTrue($result->isValid());
        $this->assertEquals('ABCDEF', $result->getFingerprint());
    }

    public function testVerifyWithKnownButUntrustedKey(): void
    {
        $gnupg = $this->createMock(GnuPGInterface::class);
        $gnupg
            ->expects($this->exactly(2))
            ->method('verify')
            ->withAnyParameters()
            ->willReturn([['fingerprint' => 'ABCDEF']]);

        $downloader = $this
            ->getMockBuilder(FileDownloader::class)
            ->onlyMethods(['downloadFile'])
            ->setConstructorArgs([sys_get_temp_dir() . '/phpcq', []])
            ->getMock();

        $verifier = new SignatureVerifier($gnupg, new KeyDownloader($downloader), []);
        $result   = $verifier->verify('foo', 'bar');

        $this->assertTrue($result->isUntrustedKey());

        $result = $verifier->verify('foo', 'bar', true);
        $this->assertTrue($result->isValid());
    }
}
