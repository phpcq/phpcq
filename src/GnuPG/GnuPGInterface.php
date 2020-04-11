<?php

declare(strict_types=1);

namespace Phpcq\GnuPG;

/**
 * Interface GnuPGInterface describes a subset of the supported features of the gnupg abstraction
 */
interface GnuPGInterface
{
    /**
     * Imports a key and returns an array with information about the importprocess.
     *
     * @param string $key THe gpg key to import
     *
     * @return array
     *
     * @see https://www.php.net/manual/function.gnupg-import.php
     */
    public function import(string $key) : array;

    /**
     *
     * @param string $search
     *
     * @return array
     */
    public function keyinfo(string $search) : array;

    /** @return array|false */
    public function verify(string $message, string $signature);
}
