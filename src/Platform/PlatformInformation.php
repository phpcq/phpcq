<?php

declare(strict_types=1);

namespace Phpcq\Platform;

use Composer\XdebugHandler\XdebugHandler;

/**
 * Class PlatformInformation
 *
 * @psee https://github.com/composer/composer/blob/master/src/Composer/Repository/PlatformRepository.php
 */
class PlatformInformation implements PlatformInformationInterface
{
    private static $extensions = null;

    private static $libraries = null;

    public function getPhpVersion(): string
    {
        return phpversion();
    }

    public function getExtensions(): array
    {
        if (null === self::$extensions) {
            self::$extensions = [];
            $loadedExtensions = get_loaded_extensions();

            // Extensions scanning
            foreach ($loadedExtensions as $name) {
                if (in_array($name, array('standard', 'Core'))) {
                    continue;
                }

                $reflExt = new \ReflectionExtension($name);
                $prettyVersion = $reflExt->getVersion();

                self::$extensions['ext-' . $name] = $prettyVersion;
            }

            // Check for Xdebug in a restarted process
            if (!in_array('xdebug', $loadedExtensions, true) && ($prettyVersion = XdebugHandler::getSkippedVersion())) {
                self::$extensions['ext-xdebug'] = $prettyVersion;
            }
        }

        return self::$extensions;
    }

    public function getLibraries() : array
    {
        if (null === self::$libraries) {
            self::$extensions = [];
            $loadedExtensions = get_loaded_extensions();

            // Another quick loop, just for possible libraries
            // Doing it this way to know that functions or constants exist before
            // relying on them.
            foreach ($loadedExtensions as $name) {
                $prettyVersion = null;
                $description = 'The '.$name.' PHP library';
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
                        $prettyVersion = preg_replace_callback('{^(?:OpenSSL|LibreSSL)?\s*([0-9.]+)([a-z]*).*}i', function ($match) {
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

                            return $match[1].'.'.$patchVersion;
                        }, OPENSSL_VERSION_TEXT);

                        $description = OPENSSL_VERSION_TEXT;
                        break;

                    case 'pcre':
                        $prettyVersion = preg_replace('{^(\S+).*}', '$1', PCRE_VERSION);
                        break;

                    case 'uuid':
                        $prettyVersion = phpversion('uuid');
                        break;

                    case 'xsl':
                        $prettyVersion = LIBXSLT_DOTTED_VERSION;
                        break;

                    default:
                        // None handled extensions have no special cases, skip
                        continue 2;
                }

                self::$libraries['lib-' . $name] = $prettyVersion;
            }
        }

        return self::$libraries;
    }

    public function getInstalledVersion(string $name) : ?string
    {
        if ($name === 'php') {
            return $this->getPhpVersion();
        }

        [$prefix, $shortName] = explode('-', $name, 2);

        if ($name === null) {
            return null;
        }

        switch ($prefix) {
            case 'ext':
                return $this->getExtensions()[$name] ?? null;

            case 'lib':
                return $this->getLibraries()[$name] ?? null;

            default:
                return null;
        }
    }

    private function initialize(): void
    {
        self::$extensions = [];
        $loadedExtensions = get_loaded_extensions();

        // Extensions scanning
        foreach ($loadedExtensions as $name) {
            if (in_array($name, array('standard', 'Core'))) {
                continue;
            }

            $reflExt = new \ReflectionExtension($name);
            $prettyVersion = $reflExt->getVersion();

            self::$extensions['ext-' . $name] = $prettyVersion;
        }

        // Check for Xdebug in a restarted process
        if (!in_array('xdebug', $loadedExtensions, true) && ($prettyVersion = XdebugHandler::getSkippedVersion())) {
            self::$extensions['ext-xdebug'] = $prettyVersion;
        }
    }
}