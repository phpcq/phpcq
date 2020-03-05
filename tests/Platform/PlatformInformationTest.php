<?php

declare(strict_types=1);

namespace Platform;

use Phpcq\Platform\PlatformInformation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlatformInformationTest extends TestCase
{
    public function testPhpVersion(): void
    {
        $platformInformation = new PlatformInformation();

        $this->assertSame(phpversion(), $platformInformation->getPhpVersion());
    }

    public function testExtensions(): void
    {
        $platformInformation = new PlatformInformation();
        $loadedExtensions = array_filter(
            get_loaded_extensions(),
            static function ($value) : bool {
                return !in_array($value, ['standard', 'Core']);
            }
        );

        $this->assertCount(count($loadedExtensions), $platformInformation->getExtensions());

    }

    public function testLibraries(): void
    {
        $platformInformation = new PlatformInformation();
        $libraries = $platformInformation->getLibraries();
        $loadedExtensions = get_loaded_extensions();

        foreach ($libraries as $name => $version) {
            $this->assertStringStartsWith('lib-', $name);
            $name = $name === 'lib-ICU' ? 'intl' : substr($name, 4);
            $this->assertContains($name, $loadedExtensions);
        }
    }

    public function testGetInstalledVersion(): void
    {
        /** @var PlatformInformation|MockObject $mock */
        $mock = $this->getMockBuilder(PlatformInformation::class)
            ->onlyMethods(['getPhpVersion', 'getLibraries', 'getExtensions'])
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('getPhpVersion')
            ->willReturn('7.4.0');

        $mock
            ->expects($this->exactly(3))
            ->method('getExtensions')
            ->willReturn(
                [
                    'ext-json' => '1.0.0',
                    'ext-pdo'  => '7.2.0'
                ]
            );

        $mock
            ->expects($this->exactly(3))
            ->method('getLibraries')
            ->willReturn(
                [
                    'lib-ICU' => '1.0.0',
                    'lib-curl'=> '7.68.0'
                ]
            );

        $this->assertSame('7.4.0', $mock->getInstalledVersion('php'));

        $this->assertSame('1.0.0', $mock->getInstalledVersion('ext-json'));
        $this->assertSame('7.2.0', $mock->getInstalledVersion('ext-pdo'));
        $this->assertNull($mock->getInstalledVersion('ext-foo'));

        $this->assertSame('1.0.0', $mock->getInstalledVersion('lib-ICU'));
        $this->assertSame('7.68.0', $mock->getInstalledVersion('lib-curl'));
        $this->assertNull($mock->getInstalledVersion('lib-foo'));
    }
}