<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Platform;

use Phpcq\Runner\Platform\PlatformInformation;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Platform\PlatformInformation
 */
class PlatformInformationTest extends TestCase
{
    public function testCustomValues(): void
    {
        $platformInformation = new PlatformInformation('7.1.0', ['ext-foo' => '1.1.0'], ['lib-foo' => '8.1.0']);

        $this->assertSame('7.1.0', $platformInformation->getPhpVersion());
        $this->assertSame(['ext-foo' => '1.1.0'], $platformInformation->getExtensions());
        $this->assertSame(['lib-foo' => '8.1.0'], $platformInformation->getLibraries());
    }

    public function testPhpVersion(): void
    {
        $platformInformation = PlatformInformation::createFromCurrentPlatform();
        $this->assertSame(phpversion(), $platformInformation->getPhpVersion());
    }

    public function testCurrentPlatformExtensions(): void
    {
        $platformInformation = PlatformInformation::createFromCurrentPlatform();
        $loadedExtensions = array_filter(
            get_loaded_extensions(),
            static function ($value): bool {
                return !in_array($value, ['standard', 'Core']);
            }
        );

        $this->assertCount(count($loadedExtensions), $platformInformation->getExtensions());
    }

    public function testCurrentPlatformInformation(): void
    {
        $platformInformation = PlatformInformation::createFromCurrentPlatform();

        $libraries = $platformInformation->getLibraries();
        $loadedExtensions = get_loaded_extensions();

        foreach (array_keys($libraries) as $name) {
            $this->assertStringStartsWith('lib-', $name);
            $name = $name === 'lib-ICU' ? 'intl' : substr($name, 4);
            $this->assertContains($name, $loadedExtensions);
        }
    }

    public function testGetInstalledVersion(): void
    {
        $platformInformation = new PlatformInformation(
            '7.4.0',
            [
                'ext-json' => '1.0.0',
                'ext-pdo'  => '7.2.0'
            ],
            [
                'lib-ICU'  => '1.0.0',
                'lib-curl' => '7.68.0',
            ]
        );

        $this->assertSame('7.4.0', $platformInformation->getInstalledVersion('php'));

        $this->assertSame('1.0.0', $platformInformation->getInstalledVersion('ext-json'));
        $this->assertSame('7.2.0', $platformInformation->getInstalledVersion('ext-pdo'));
        $this->assertNull($platformInformation->getInstalledVersion('ext-foo'));

        $this->assertSame('1.0.0', $platformInformation->getInstalledVersion('lib-ICU'));
        $this->assertSame('7.68.0', $platformInformation->getInstalledVersion('lib-curl'));
        $this->assertNull($platformInformation->getInstalledVersion('lib-foo'));
    }

    public function testMysqlndWorkaround(): void
    {
        if (!in_array('mysqlnd', get_loaded_extensions())) {
            $this->markTestSkipped('mysqlnd extension is not loaded, can not test.');
        }

        $platformInformation = PlatformInformation::createFromCurrentPlatform();
        if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
            // NOTE: as mysqlnd is bundled with php, we expect the same version here.
            self::assertSame(PHP_VERSION, $platformInformation->getInstalledVersion('ext-mysqlnd'));
            return;
        }
        // Allow to validate for older PHP versions.
        self::assertContains($platformInformation->getInstalledVersion('ext-mysqlnd'), [
            // Since PHP 7.4.0RC1
            PHP_VERSION,
            // Since PHP 7.0.0-alpha1: "mysqlnd 5.0.12-dev - 20150407 - $Id$"
            '5.0.12-dev',
            // Since PHP 5.5.0-alpha1: "mysqlnd 5.0.11-dev - 20120503 - $Id$"
            '5.0.11-dev',
        ]);
    }
}
