<?php

declare(strict_types=1);

namespace GnuPG;

use PharIo\GnuPG\Factory as PharIoGnuPGFactory;
use PharIo\GnuPG\GnuPG;
use Phpcq\GnuPG\GnuPGDecorator;
use Phpcq\GnuPG\GnuPGFactory;
use PHPUnit\Framework\TestCase;
use function class_alias;

final class GnuPGFactoryTest extends TestCase
{
    public function testCreate() : void
    {
        class_alias(GnuPG::class, '\Gnupg');

        $gnupg = $this->createMock(GnuPG::class);
        $mock = $this->createMock(PharIoGnuPGFactory::class);

        $mock->expects($this->once())
            ->method('createGnuPG')
            ->withAnyParameters()
            ->willReturn($gnupg);

        $factory = new GnuPGFactory(__DIR__, $mock);
        $instance = $factory->create();

        $this->assertInstanceOf(GnuPGDecorator::class, $instance);
    }
}
