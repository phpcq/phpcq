<?php

declare(strict_types=1);

namespace Phpcq\Platform;

use Composer\XdebugHandler\XdebugHandler;

use function curl_version;
use function defined;
use function get_loaded_extensions;
use function in_array;
use function ob_get_clean;
use function ob_start;
use function ord;
use function phpversion;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function strlen;
use function strpos;
use function strtolower;

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
 */
class PlatformInformation implements PlatformInformationInterface
{
    /** @var string */
    private $phpVersion;

    /** @param array<string, string> */
    private $extensions;

    /** @param array<string, string> */
    private $libraries;

    /**
     * PlatformInformation constructor.
     *
     * @param string                $phpVersion
     * @param array<string, string> $extensions
     * @param array<string, string> $libraries
     */
    public function __construct(string $phpVersion, array $extensions, array $libraries)
    {
        $this->phpVersion = $phpVersion;
        $this->extensions = $extensions;
        $this->libraries  = $libraries;
    }

    public static function createFromCurrentPlatform(): self
    {
        return new self(
            phpversion(),
            self::detectExtensions(),
            self::detectLibraries()
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
        if ($name === 'php') {
            return $this->getPhpVersion();
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
            if (in_array($name, array('standard', 'Core'))) {
                continue;
            }

            $reflExt = new \ReflectionExtension($name);
            $prettyVersion = $reflExt->getVersion();
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

            $extensions['ext-' . strtolower($name)] = $prettyVersion;
        }

        // Check for Xdebug in a restarted process
        if (!in_array('xdebug', $loadedExtensions, true) && ($prettyVersion = XdebugHandler::getSkippedVersion())) {
            $extensions['ext-xdebug'] = $prettyVersion;
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
        $libraries = [];
        $loadedExtensions = get_loaded_extensions();

        // Another quick loop, just for possible libraries
        // Doing it this way to know that functions or constants exist before
        // relying on them.
        foreach ($loadedExtensions as $name) {
            $prettyVersion = null;

            switch ($name) {
                case 'curl':
                    $curlVersion = curl_version();
                    $prettyVersion = $curlVersion['version'];
                    break;

                case 'iconv':
                    $prettyVersion = ICONV_VERSION;
                    break;

                case 'intl':
                    $name = 'ICU';
                    if (defined('INTL_ICU_VERSION')) {
                        $prettyVersion = INTL_ICU_VERSION;
                    } else {
                        $reflector = new \ReflectionExtension('intl');

                        ob_start();
                        $reflector->info();
                        $output = ob_get_clean();

                        preg_match('/^ICU version => (.*)$/m', $output, $matches);
                        $prettyVersion = $matches[1];
                    }

                    break;

                case 'imagick':
                    $imagick = new \Imagick();
                    $imageMagickVersion = $imagick->getVersion();
                    // 6.x: ImageMagick 6.2.9 08/24/06 Q16 http://www.imagemagick.org
                    // 7.x: ImageMagick 7.0.8-34 Q16 x86_64 2019-03-23 https://imagemagick.org
                    preg_match('/^ImageMagick ([\d.]+)(?:-(\d+))?/', $imageMagickVersion['versionString'], $matches);
                    if (isset($matches[2])) {
                        $prettyVersion = "{$matches[1]}.{$matches[2]}";
                    } else {
                        $prettyVersion = $matches[1];
                    }
                    break;

                case 'libxml':
                    $prettyVersion = LIBXML_DOTTED_VERSION;
                    break;

                case 'openssl':
                    $prettyVersion = preg_replace_callback(
                        '{^(?:OpenSSL|LibreSSL)?\s*([0-9.]+)([a-z]*).*}i',
                        function ($match) {
                            if (empty($match[2])) {
                                return $match[1];
                            }

                            // OpenSSL versions add another letter when they reach Z.
                            // e.g. OpenSSL 0.9.8zh 3 Dec 2015

                            if (!preg_match('{^z*[a-z]$}', $match[2])) {
                                // 0.9.8abc is garbage
                                return 0;
                            }

                            $len = strlen($match[2]);
                            $patchVersion = ($len - 1) * 26; // All Z
                            $patchVersion += ord($match[2][$len - 1]) - 96;

                            return $match[1] . '.' . $patchVersion;
                        },
                        OPENSSL_VERSION_TEXT
                    );

                    break;

                case 'pcre':
                    $prettyVersion = preg_replace('{^(\S+).*}', '$1', PCRE_VERSION);
                    break;

                case 'uuid':
                    $prettyVersion = phpversion('uuid');
                    break;

                case 'xsl':
                    /**
                     * @psalm-suppress UndefinedConstant
                     * @var string
                     */
                    $prettyVersion = LIBXSLT_DOTTED_VERSION;
                    break;

                default:
                    // None handled extensions have no special cases, skip
                    continue 2;
            }

            /** @var string $prettyVersion */
            $libraries['lib-' . $name] = $prettyVersion;
        }

        return $libraries;
    }
}
