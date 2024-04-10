<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Platform;

use Composer\Semver\VersionParser;
use Phpcq\Runner\Platform\PlatformInformation;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

use function in_array;
use function str_contains;
use function substr;

/**
 * @covers \Phpcq\Runner\Platform\PlatformInformation
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
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
        $this->assertSame(self::normalizeVersion(phpversion()), $platformInformation->getPhpVersion());
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

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCurrentPlatformInformation(): void
    {
        $platformInformation = PlatformInformation::createFromCurrentPlatform();

        $libraries = $platformInformation->getLibraries();
        $loadedExtensions = get_loaded_extensions();

        foreach (array_keys($libraries) as $name) {
            $this->assertStringStartsWith('lib-', $name);
            $name = substr($name, 4);

            switch ($name) {
                case 'amqp-librabbitmq':
                case 'amqp-protocol':
                    self::assertContains('amqp', $loadedExtensions);
                    break;

                case 'curl-zlib':
                case 'curl-libssh':
                case 'curl-libssh2':
                case 'curl-openssl':
                case 'curl-openssl-fips':
                case 'curl-securetransport':
                    self::assertContains('curl', $loadedExtensions);
                    break;

                case 'date-timelib':
                case 'date-timezonedb-zoneinfo':
                case 'date-zoneinfo':
                    self::assertContains('date', $loadedExtensions);

                    break;

                case 'fileinfo-libmagic':
                    self::assertContains('fileinfo', $loadedExtensions);

                    break;

                case 'gd-libjpeg':
                case 'gd-freetype':
                case 'gd-libxpm':
                    self::assertContains('gd', $loadedExtensions);

                    break;

                case 'icu':
                case 'icu-zoneinfo':
                case 'icu-cldr':
                case 'icu-unicode':
                    self::assertContains('intl', $loadedExtensions);

                    break;

                case 'imagick-imagemagick':
                    self::assertContains('imagick', $loadedExtensions);

                    break;

                case 'ldap-openldap':
                    self::assertContains('ldap', $loadedExtensions);

                    break;

                case 'libsodium':
                    self::assertTrue(in_array('libsodium', $loadedExtensions) || in_array('sodium', $loadedExtensions));

                    break;

                case 'libxslt':
                case 'libxslt-libxml':
                    self::assertContains('xsl', $loadedExtensions);

                    break;

                case 'mbstring-oniguruma':
                case 'mbstring-libmbfl':
                    self::assertContains('mbstring', $loadedExtensions);

                    break;

                case 'mysqlnd-mysqlnd':
                    self::assertContains('mysqlnd', $loadedExtensions);

                    break;

                case 'mongodb-libmongoc':
                case 'mongodb-libbson':
                    self::assertContains('mongodb', $loadedExtensions);

                    break;

                case 'pdo_mysql-mysqlnd':
                    self::assertContains('pdo_mysql', $loadedExtensions);

                    break;

                case 'pcre-unicode':
                    self::assertContains('pcre', $loadedExtensions);

                    break;

                case 'pdo_pgsql-libpq':
                    self::assertContains('pdo_pgsql', $loadedExtensions);

                    break;

                case 'pdo_sqlite-sqlite':
                    self::assertContains('pdo_sqlite', $loadedExtensions);

                    break;

                case 'sqlite3-sqlite':
                    self::assertContains('sqlite3', $loadedExtensions);

                    break;

                case 'pgsql-libpq':
                    self::assertContains('pgsql', $loadedExtensions);

                    break;

                case 'dom-libxml':
                case 'xml-libxml':
                case 'simplexml-libxml':
                case 'xmlreader-libxml':
                case 'xmlwriter-libxml':
                    self::assertContains('libxml', $loadedExtensions);

                    break;

                case 'yaml-libyaml':
                    self::assertContains('yaml', $loadedExtensions);

                    break;

                case 'zip-libzip':
                    self::assertContains('zip', $loadedExtensions);

                    break;

                default:
                    self::assertContains($name, $loadedExtensions);
            }
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
            self::assertSame(
                self::normalizeVersion(PHP_VERSION),
                $platformInformation->getInstalledVersion('ext-mysqlnd')
            );
            return;
        }
        // Allow to validate for older PHP versions.
        self::assertContains($platformInformation->getInstalledVersion('ext-mysqlnd'), [
            // Since PHP 7.4.0RC1
            self::normalizeVersion(PHP_VERSION),
            // Since PHP 7.0.0-alpha1: "mysqlnd 5.0.12-dev - 20150407 - $Id$"
            '5.0.12.0-dev',
            // Since PHP 5.5.0-alpha1: "mysqlnd 5.0.11-dev - 20120503 - $Id$"
            '5.0.11.0-dev',
        ]);
    }

    private static function normalizeVersion($prettyVersion): string
    {
        $versionParser = new VersionParser();
        try {
            return $versionParser->normalize($prettyVersion);
        } catch (UnexpectedValueException $e) {
            return $versionParser->normalize(preg_replace('#^([^~+-]+).*$#', '$1', $prettyVersion));
        }
    }
}
