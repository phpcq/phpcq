<?php

declare(strict_types=1);

namespace Phpcq\Test\GnuPG;

use PharIo\GnuPG\GnuPG;
use Phpcq\Exception\GnuPGException;
use Phpcq\GnuPG\GnuPGDecorator;
use PHPUnit\Framework\TestCase;
use function class_alias;
use function class_exists;

final class GnuPGDecoratorTest extends TestCase
{
    public function setUp() : void
    {
        if (!class_exists('Gnupg')) {
            class_alias(GnuPG::class, '\Gnupg');
        }
    }

    public function testImport() : void
    {
        $mock = $this->createMock(\Gnupg::class);
        $mock
            ->expects($this->once())
            ->method('import')
            ->withAnyParameters()
            ->willReturn(['imported' => 1]);

        $decorator = new GnuPGDecorator($mock);
        $result    = $decorator->import('foo');

        $this->assertEquals(['imported' => 1], $result);
    }

    public function testImportFailure() : void
    {
        $mock = $this->createMock(\Gnupg::class);
        $mock
            ->expects($this->once())
            ->method('import')
            ->withAnyParameters()
            ->willReturn(['imported' => 0]);

        $this->expectException(GnuPGException::class);

        $decorator = new GnuPGDecorator($mock);
        $decorator->import('foo');
    }

    public function testKeyinfo() : void
    {
        $mock = $this->createMock(\Gnupg::class);
        $mock
            ->expects($this->once())
            ->method('keyinfo')
            ->withAnyParameters()
            ->willReturn(['fingerprint' => 'ABCDEF']);

        $decorator = new GnuPGDecorator($mock);
        $result    = $decorator->keyinfo('foo');

        $this->assertEquals(['fingerprint' => 'ABCDEF'], $result);
    }

    public function testVerify() : void
    {
        $mock = $this->createMock(\Gnupg::class);
        $mock
            ->expects($this->once())
            ->method('verify')
            ->withAnyParameters()
            ->willReturn(true);

        $decorator = new GnuPGDecorator($mock);
        $result    = $decorator->verify('foo', 'bar');

        $this->assertTrue($result);
    }
}
