<?php

declare(strict_types=1);

namespace Phpcq\Runner\Platform;

use Composer\Pcre\Preg;
use Composer\Semver\VersionParser;
use Composer\XdebugHandler\XdebugHandler;
use Imagick;
use IntlChar;
use ReflectionExtension;
use ResourceBundle;
use UnexpectedValueException;
use ZipArchive;

use function class_exists;
use function curl_version;
use function defined;
use function get_loaded_extensions;
use function in_array;
use function ob_get_clean;
use function ob_start;
use function phpversion;
use function preg_match;
use function str_replace;
use function strpos;
use function strtolower;

use const GMP_VERSION;
use const ICONV_VERSION;
use const INTL_ICU_VERSION;
use const LIBXML_DOTTED_VERSION;
use const LIBXSLT_DOTTED_VERSION;
use const OPENSSL_VERSION_TEXT;
use const PCRE_VERSION;

/**
 * Class PlatformInformation
 *
 * @psee https://github.com/composer/composer/blob/master/src/Composer/Repository/PlatformRepository.php
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PlatformInformation implements PlatformInformationInterface
{
    public static VersionParser $versionParser;

    /**
     * PlatformInformation constructor.
     *
     * @param string                $phpVersion
     * @param array<string, string> $extensions
     * @param array<string, string> $libraries
     */
    public function __construct(private string $phpVersion, private array $extensions, private array $libraries)
    {
    }

    public static function createFromCurrentPlatform(): self
    {
        if (!isset(static::$versionParser)) {
            static::$versionParser = new VersionParser();
        }
        return new self(
            self::normalizeVersion(phpversion()),
            self::detectExtensions(),
            self::detectLibraries(),
        );
    }

    public function getPhpVersion(): string
    {
        return $this->phpVersion;
    }

    /**
     * @return array<string, string>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * @return array<string, string>
     */
    public function getLibraries(): array
    {
        return $this->libraries;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function getInstalledVersion(string $name): ?string
    {
        // PHP (php and the subtypes: php-64bit, php-ipv6, php-zts php-debug)
        if (1 === preg_match('#^php(?:-(?:64bit|ipv6|zts|debug))?$#', $name)) {
            return $this->getPhpPackageVersion($name);
        }

        if (strpos($name, '-') === false) {
            return null;
        }

        [$prefix] = explode('-', $name, 2);

        switch ($prefix) {
            case 'ext':
                return $this->getExtensions()[strtolower($name)] ?? null;

            case 'lib':
                return $this->getLibraries()[$name] ?? null;

            default:
                return null;
        }
    }

    /** @return array<string, string> */
    private static function detectExtensions(): array
    {
        $loadedExtensions = get_loaded_extensions();
        $extensions       = [];

        // Extensions scanning
        foreach ($loadedExtensions as $name) {
            if (in_array($name, ['standard', 'Core'])) {
                continue;
            }

            $prettyVersion = self::getExtensionVersion($name);

            // TODO: Do we need the mysqlnd special handling here? Composer has removed it
            // "ext-mysqlnd" has an obscure version string:
            // - since PHP 7.4.0: "mysqlnd 7.4.4"
            // - pre PHP 7.4.0: "mysqlnd 5.0.12-dev - 20150407 - $Id$"
            // See:
            // - https://github.com/php/php-src/blob/1c334db4c818eb4175e9e246f3fc5d91bcfe1eef/ext/mysqlnd/mysqlnd.h#L22
            // - https://github.com/php/php-src/blob/da1816c3d37da03c62d0086e6228625ac006abec/ext/mysqlnd/mysqlnd.h#L24
            // We use strncmp here to be able to skip the heavy regex calculation for all other extensions.
            if (0 === strncmp($prettyVersion, 'mysqlnd ', 8)) {
                if (preg_match('#mysqlnd ([^ ]*)#', $prettyVersion, $matches)) {
                    $prettyVersion = $matches[1];
                }
            }

            $extensionName              = 'ext-' . str_replace(' ', '-', strtolower($name));
            $extensions[$extensionName] = self::normalizeVersion($prettyVersion);
        }

        // Check for Xdebug in a restarted process
        if (!in_array('xdebug', $loadedExtensions, true) && ($prettyVersion = XdebugHandler::getSkippedVersion())) {
            $extensions['ext-xdebug'] = self::normalizeVersion($prettyVersion);
        }

        return $extensions;
    }

    /**
     * @return array<string, string>
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private static function detectLibraries(): array
    {
        $libraries = new class ([self::class, 'normalizeVersion']) {
            /** @var callable(string): string $normalizeVersion */
            private $normalizeVersion;

            /** @param callable(string): string $normalizeVersion */
            public function __construct(callable $normalizeVersion)
            {

                $this->normalizeVersion = $normalizeVersion;
            }
            /** @var array<non-empty-string, non-empty-string> */
            private array $libraries = [];

            public function add(string $name, string $prettyVersion)
            {
                try {
                    /** @var string $prettyVersion */
                    $this->libraries['lib-' . $name] = ($this->normalizeVersion)($prettyVersion);
                } catch (UnexpectedValueException $e) {
                    // This may occur eg. for alpine linux PHP and lib-iconv which reports as "unknown".
                }
            }

            /** @return array<non-empty-string, non-empty-string> */
            public function toArray(): array
            {
                return $this->libraries;
            }
        };
        $loadedExtensions = get_loaded_extensions();

        // Another quick loop, just for possible libraries
        // Doing it this way to know that functions or constants exist before
        // relying on them.
        foreach ($loadedExtensions as $name) {
            $prettyVersion = null;

            switch ($name) {
                case 'amqp':
                    $info = self::getExtensionInfo($name);

                    if (Preg::isMatch('/^librabbitmq version => (?<version>.+)$/im', $info, $librabbitmqMatches)) {
                        $libraries->add($name . '-librabbitmq', $librabbitmqMatches['version']);
                    }

                    // AMQP protocol version => 0-9-1
                    if (
                        Preg::isMatchStrictGroups(
                            '/^AMQP protocol version => (?<version>.+)$/im',
                            $info,
                            $protocolMatches
                        )
                    ) {
                        $libraries->add($name . '-protocol', $protocolMatches['version']);
                    }

                    break;

                case 'bz2':
                    $info = self::getExtensionInfo($name);

                    // BZip2 Version => 1.0.6, 6-Sept-2010
                    if (Preg::isMatch('/^BZip2 Version => (?<version>.*),/im', $info, $matches)) {
                        $libraries->add($name, $matches['version']);
                    }

                    break;

                case 'curl':
                    $libraries->add($name, curl_version()['version']);

                    $info = self::getExtensionInfo($name);

                    // SSL Version => OpenSSL/1.0.1t
                    if (
                        Preg::isMatchStrictGroups(
                            '{^SSL Version => (?<library>[^/]+)/(?<version>.+)$}im',
                            $info,
                            $sslMatches
                        )
                    ) {
                        $library = strtolower($sslMatches['library']);
                        if ($library === 'openssl') {
                            $parsedVersion = Version::parseOpenssl($sslMatches['version'], $isFips);
                            $libraries->add($name . '-openssl', $parsedVersion);
                            if ($isFips) {
                                $libraries->add($name . '-openssl-fips', $parsedVersion);
                            }
                        } else {
                            if ($library === '(securetransport) openssl') {
                                $shortlib = 'securetransport';
                            } else {
                                $shortlib = $library;
                            }
                            $libraries->add($name . '-' . $shortlib, $sslMatches['version']);
                            $libraries->add($name . '-openssl' . $shortlib, $sslMatches['version']);
                        }
                    }

                    // libSSH Version => libssh2/1.4.3
                    if (
                        Preg::isMatchStrictGroups(
                            '{^libSSH Version => (?<library>[^/]+)/(?<version>.+?)(?:/.*)?$}im',
                            $info,
                            $sshMatches
                        )
                    ) {
                        $libraries->add($name . '-' . strtolower($sshMatches['library']), $sshMatches['version']);
                    }

                    // ZLib Version => 1.2.8
                    if (
                        Preg::isMatchStrictGroups('{^ZLib Version => (?<version>.+)$}im', $info, $zlibMatches)
                    ) {
                        $libraries->add($name . '-zlib', $zlibMatches['version']);
                    }

                    break;

                case 'date':
                    $info = self::getExtensionInfo($name);

                    // timelib version => 2018.03
                    if (
                        Preg::isMatchStrictGroups(
                            '/^timelib version => (?<version>.+)$/im',
                            $info,
                            $timelibMatches
                        )
                    ) {
                        $libraries->add($name . '-timelib', $timelibMatches['version']);
                    }

                    // Timezone Database => internal
                    if (
                        Preg::isMatchStrictGroups(
                            '/^Timezone Database => (?<source>internal|external)$/im',
                            $info,
                            $zoneinfoSourceMatches
                        )
                    ) {
                        $external = $zoneinfoSourceMatches['source'] === 'external';
                        if (
                            Preg::isMatchStrictGroups(
                                '/^"Olson" Timezone Database Version => (?<version>.+?)(?:\.system)?$/im',
                                $info,
                                $zoneinfoMatches
                            )
                        ) {
                            // If the timezonedb is provided by ext/timezonedb, register that version as a replacement
                            if ($external && in_array('timezonedb', $loadedExtensions, true)) {
                                $libraries->add('timezonedb-zoneinfo', $zoneinfoMatches['version']);
                            }

                            $libraries->add($name . '-zoneinfo', $zoneinfoMatches['version']);
                        }
                    }
                    break;

                case 'fileinfo':
                    $info = self::getExtensionInfo($name);

                    // libmagic => 537
                    if (Preg::isMatch('/^libmagic => (?<version>.+)$/im', $info, $magicMatches)) {
                        $libraries->add($name . '-libmagic', $magicMatches['version']);
                    }
                    break;

                case 'gd':
                    $libraries->add($name, 'GD_VERSION');

                    $info = self::getExtensionInfo($name);

                    if (
                        Preg::isMatchStrictGroups(
                            '/^libJPEG Version => (?<version>.+?)(?: compatible)?$/im',
                            $info,
                            $libjpegMatches
                        )
                    ) {
                        $libraries->add($name . '-libjpeg', Version::parseLibjpeg($libjpegMatches['version']));
                    }

                    if (Preg::isMatchStrictGroups('/^libPNG Version => (?<version>.+)$/im', $info, $libpngMatches)) {
                        $libraries->add($name . '-libpng', $libpngMatches['version']);
                    }

                    if (
                        Preg::isMatchStrictGroups(
                            '/^FreeType Version => (?<version>.+)$/im',
                            $info,
                            $freetypeMatches
                        )
                    ) {
                        $libraries->add($name . '-freetype', $freetypeMatches['version']);
                    }

                    if (Preg::isMatchStrictGroups('/^libXpm Version => (?<versionId>\d+)$/im', $info, $libxpmMatches)) {
                        $libraries->add(
                            $name . '-libxpm',
                            Version::convertLibxpmVersionId((int) $libxpmMatches['versionId'])
                        );
                    }

                    break;

                case 'gmp':
                    $libraries->add($name, GMP_VERSION);

                    break;

                case 'iconv':
                    $libraries->add($name, (string) ICONV_VERSION);
                    break;

                case 'intl':
                    $info = self::getExtensionInfo($name);

                    // Truthy check is for testing only so we can make the condition fail
                    if (defined(INTL_ICU_VERSION)) {
                        $libraries->add('icu', INTL_ICU_VERSION);
                    } elseif (Preg::isMatch('/^ICU version => (?<version>.+)$/im', $info, $matches)) {
                        $libraries->add('icu', $matches['version']);
                    }

                    // ICU TZData version => 2019c
                    if (
                        Preg::isMatchStrictGroups(
                            '/^ICU TZData version => (?<version>.*)$/im',
                            $info,
                            $zoneinfoMatches
                        ) && null !== ($version = Version::parseZoneinfoVersion($zoneinfoMatches['version']))
                    ) {
                        $libraries->add('icu-zoneinfo', $version);
                    }

                    // Add a separate version for the CLDR library version
                    if (class_exists(ResourceBundle::class)) {
                        $resourceBundle = ResourceBundle::create('root', 'ICUDATA', false);
                        if ($resourceBundle !== null) {
                            $libraries->add('icu-cldr', $resourceBundle->get('Version'));
                        }
                    }

                    if (class_exists(IntlChar::class)) {
                        $libraries->add('icu-unicode', implode('.', array_slice(IntlChar::getUnicodeVersion(), 0, 3)));
                    }

                    break;

                case 'imagick':
                    $imageMagickVersion = (new Imagick())->getVersion();
                    // 6.x: ImageMagick 6.2.9 08/24/06 Q16 http://www.imagemagick.org
                    // 7.x: ImageMagick 7.0.8-34 Q16 x86_64 2019-03-23 https://imagemagick.org
                    Preg::match(
                        '/^ImageMagick (?<version>[\d.]+)(?:-(?<patch>\d+))?/',
                        $imageMagickVersion['versionString'],
                        $matches
                    );
                    $version = $matches['version'];
                    if (isset($matches['patch'])) {
                        $version .= '.' . $matches['patch'];
                    }

                    $libraries->add($name, $version);
                    $libraries->add($name . '-imagemagick', $version);

                    break;

                case 'ldap':
                    $info = self::getExtensionInfo($name);

                    if (
                        Preg::isMatchStrictGroups('/^Vendor Version => (?<versionId>\d+)$/im', $info, $matches)
                        && Preg::isMatchStrictGroups('/^Vendor Name => (?<vendor>.+)$/im', $info, $vendorMatches)
                    ) {
                        $libraries->add(
                            $name . '-' . strtolower($vendorMatches['vendor']),
                            Version::convertOpenldapVersionId((int) $matches['versionId'])
                        );
                    }

                    break;

                case 'libxml':
                    // ext/dom, ext/simplexml, ext/xmlreader and ext/xmlwriter use the same libxml as the ext/libxml
                    $libxmlProvides = array_map(static function ($extension): string {
                        return $extension . '-libxml';
                    }, array_intersect($loadedExtensions, ['dom', 'simplexml', 'xml', 'xmlreader', 'xmlwriter']));

                    $libraries->add($name, LIBXML_DOTTED_VERSION);
                    ;

                    foreach ($libxmlProvides as $provide) {
                        $libraries->add($provide, LIBXML_DOTTED_VERSION);
                        ;
                    }

                    break;

                case 'mbstring':
                    $info = self::getExtensionInfo($name);

                    // libmbfl version => 1.3.2
                    if (Preg::isMatch('/^libmbfl version => (?<version>.+)$/im', $info, $libmbflMatches)) {
                        $libraries->add($name . '-libmbfl', $libmbflMatches['version']);
                    }

                    if (defined(MB_ONIGURUMA_VERSION)) {
                        $libraries->add($name . '-oniguruma', 'MB_ONIGURUMA_VERSION');

                        // Multibyte regex (oniguruma) version => 5.9.5
                        // oniguruma version => 6.9.0
                    } elseif (
                        Preg::isMatch(
                            '/^(?:oniguruma|Multibyte regex \(oniguruma\)) version => (?<version>.+)$/im',
                            $info,
                            $onigurumaMatches
                        )
                    ) {
                        $libraries->add($name . '-oniguruma', $onigurumaMatches['version']);
                    }

                    break;

                case 'memcached':
                    $info = self::getExtensionInfo($name);

                    // libmemcached version => 1.0.18
                    if (Preg::isMatch('/^libmemcached version => (?<version>.+)$/im', $info, $matches)) {
                        $libraries->add($name . '-libmemcached', $matches['version']);
                    }

                    break;

                case 'openssl':
                    // OpenSSL 1.1.1g  21 Apr 2020
                    if (
                        Preg::isMatchStrictGroups(
                            '{^(?:OpenSSL|LibreSSL)?\s*(?<version>\S+)}i',
                            OPENSSL_VERSION_TEXT,
                            $matches
                        )
                    ) {
                        $parsedVersion = Version::parseOpenssl($matches['version'], $isFips);
                        $libraries->add($name, $parsedVersion);

                        if ($isFips) {
                            $libraries->add($name . '-fips', $parsedVersion);
                        }
                    }
                    break;

                case 'pcre':
                    $libraries->add($name, Preg::replace('{^(\S+).*}', '$1', PCRE_VERSION));

                    $info = self::getExtensionInfo($name);

                    // PCRE Unicode Version => 12.1.0
                    if (
                        Preg::isMatchStrictGroups(
                            '/^PCRE Unicode Version => (?<version>.+)$/im',
                            $info,
                            $pcreUnicodeMatches
                        )
                    ) {
                        $libraries->add($name . '-unicode', $pcreUnicodeMatches['version']);
                    }

                    break;

                case 'mysqlnd':
                case 'pdo_mysql':
                    $info = self::getExtensionInfo($name);

                    if (
                        Preg::isMatchStrictGroups(
                            '/^(?:Client API version|Version) => mysqlnd (?<version>.+?) /mi',
                            $info,
                            $matches
                        )
                    ) {
                        $libraries->add($name . '-mysqlnd', $matches['version']);
                    }

                    break;

                case 'mongodb':
                    $info = self::getExtensionInfo($name);

                    if (
                        Preg::isMatchStrictGroups(
                            '/^libmongoc bundled version => (?<version>.+)$/im',
                            $info,
                            $libmongocMatches
                        )
                    ) {
                        $libraries->add($name . '-libmongoc', $libmongocMatches['version']);
                    }

                    if (
                        Preg::isMatchStrictGroups(
                            '/^libbson bundled version => (?<version>.+)$/im',
                            $info,
                            $libbsonMatches
                        )
                    ) {
                        $libraries->add($name . '-libbson', $libbsonMatches['version']);
                    }
                    break;

                case 'pgsql':
                    if (defined('PGSQL_LIBPQ_VERSION')) {
                        $libraries->add('pgsql-libpq', PGSQL_LIBPQ_VERSION);
                        break;
                    }
                // intentional fall-through to next case...

                case 'pdo_pgsql':
                    $info = self::getExtensionInfo($name);

                    if (Preg::isMatch('/^PostgreSQL\(libpq\) Version => (?<version>.*)$/im', $info, $matches)) {
                        $libraries->add($name . '-libpq', $matches['version']);
                    }
                    break;

                case 'pq':
                    $info = self::getExtensionInfo($name);

                    // Used Library => Compiled => Linked
                    // libpq => 14.3 (Ubuntu 14.3-1.pgdg22.04+1) => 15.0.2
                    if (Preg::isMatch('/^libpq => (?<compiled>.+) => (?<linked>.+)$/im', $info, $matches)) {
                        $libraries->add($name . '-libpq', $matches['linked']);
                    }
                    break;

                case 'rdkafka':
                    if (defined('RD_KAFKA_VERSION')) {
                        /**
                         * Interpreted as hex \c MM.mm.rr.xx:
                         *  - MM = Major
                         *  - mm = minor
                         *  - rr = revision
                         *  - xx = pre-release id (0xff is the final release)
                         *
                         * pre-release ID in practice is always 0xff even for RCs etc, so we ignore it
                         */
                        $libRdKafkaVersionInt = RD_KAFKA_VERSION;
                        $libraries->add(
                            $name . '-librdkafka',
                            sprintf(
                                '%d.%d.%d',
                                ($libRdKafkaVersionInt & 0xFF000000) >> 24,
                                ($libRdKafkaVersionInt & 0x00FF0000) >> 16,
                                ($libRdKafkaVersionInt & 0x0000FF00) >> 8
                            )
                        );
                    }
                    break;

                case 'libsodium':
                case 'sodium':
                    if (defined('SODIUM_LIBRARY_VERSION')) {
                        $libraries->add('libsodium', SODIUM_LIBRARY_VERSION);
                    }
                    break;

                case 'sqlite3':
                case 'pdo_sqlite':
                    $info = self::getExtensionInfo($name);

                    if (Preg::isMatch('/^SQLite Library => (?<version>.+)$/im', $info, $matches)) {
                        $libraries->add($name . '-sqlite', $matches['version']);
                    }
                    break;

                case 'ssh2':
                    $info = self::getExtensionInfo($name);

                    if (Preg::isMatch('/^libssh2 version => (?<version>.+)$/im', $info, $matches)) {
                        $libraries->add($name . '-libssh2', $matches['version']);
                    }
                    break;

                case 'uuid':
                    $libraries->add($name, self::getExtensionVersion($name));
                    break;

                case 'xsl':
                    $libraries->add($name, LIBXSLT_DOTTED_VERSION);
                    $libraries->add('libxslt', LIBXSLT_DOTTED_VERSION);

                    $info = self::getExtensionInfo('xsl');
                    if (
                        Preg::isMatch(
                            '/^libxslt compiled against libxml Version => (?<version>.+)$/im',
                            $info,
                            $matches
                        )
                    ) {
                        $libraries->add('libxslt-libxml', $matches['version']);
                    }
                    break;

                case 'yaml':
                    $info = self::getExtensionInfo('yaml');

                    if (Preg::isMatch('/^LibYAML Version => (?<version>.+)$/im', $info, $matches)) {
                        $libraries->add($name . '-libyaml', $matches['version']);
                    }
                    break;

                case 'zip':
                    if (defined('ZipArchive::LIBZIP_VERSION')) {
                        $libraries->add($name, ZipArchive::LIBZIP_VERSION);
                        $libraries->add($name . '-libzip', ZipArchive::LIBZIP_VERSION);
                    }
                    break;

                case 'zlib':
                    if (defined('ZLIB_VERSION')) {
                        $libraries->add($name, ZLIB_VERSION);

                        // Linked Version => 1.2.8
                    } elseif (
                        Preg::isMatch(
                            '/^Linked Version => (?<version>.+)$/im',
                            self::getExtensionInfo($name),
                            $matches
                        )
                    ) {
                        $libraries->add($name, $matches['version']);
                    }
                    break;

                default:
                    break;
            }
        }

        return $libraries->toArray();
    }

    private static function getExtensionVersion(string $extension): string
    {
        $version = phpversion($extension);
        if ($version === false) {
            $version = '0';
        }

        return $version;
    }

    private static function getExtensionInfo(string $extension): string
    {
        $reflector = new ReflectionExtension($extension);

        ob_start();
        $reflector->info();

        return ob_get_clean();
    }

    public static function normalizeVersion($prettyVersion): string
    {
        try {
            return self::$versionParser->normalize($prettyVersion);
        } catch (UnexpectedValueException $e) {
            if (Preg::isMatchStrictGroups('{^(\d+\.\d+\.\d+(?:\.\d+)?)}', $prettyVersion, $match)) {
                $prettyVersion = $match[1];
            } else {
                $prettyVersion = '0';
            }

            return self::$versionParser->normalize($prettyVersion);
        }
    }

    private function getPhpPackageVersion(string $name): ?string
    {
        switch ($name) {
            case 'php':
                return $this->getPhpVersion();
            case 'php-64bit':
                return $this->getPhpVersion64Bit();
            case 'php-ipv6':
                return $this->getPhpVersionIpv6();
            case 'php-zts':
                return $this->getPhpVersionZts();
            case 'php-debug':
                return $this->getPhpVersionDebug();
            default:
        }

        throw new UnexpectedValueException(sprintf('Unknown package name "%s"', $name));
    }

    private function getPhpVersion64Bit(): ?string
    {
        if (\PHP_INT_SIZE === 8) {
            return $this->getPhpVersion();
        }
        return null;
    }

    private function getPhpVersionIpv6(): ?string
    {
        if (\defined('AF_INET6') /** composer also checks if inet_pton supports '::'. */) {
            return $this->getPhpVersion();
        }
        return null;
    }

    private function getPhpVersionZts(): ?string
    {
        if (\defined('PHP_ZTS') && \PHP_ZTS) {
            return $this->getPhpVersion();
        }
        return null;
    }

    private function getPhpVersionDebug(): ?string
    {
        if (\PHP_DEBUG) {
            return $this->getPhpVersion();
        }
        return null;
    }
}
