<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\TrustedKeys;

interface TrustedKeyCollectionInterface
{
    public function add(string ... $keys) : void;

    public function remove(string $key) : void;

    public function contains(string $key) : bool;

    public function toArray() : array;
}
