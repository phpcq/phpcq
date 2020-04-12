<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\TrustedKeys;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;

final class TrustedKeyStorage implements TrustedKeyCollectionInterface
{
    /** @var TrustedKeyCollection */
    private $collection;

    /** @var string */
    private $storagePath;

    /**
     * TrustedKeyStorage constructor.
     *
     * @param TrustedKeyCollection $collection
     */
    public function __construct(string $storagePath)
    {
        $this->storagePath = $storagePath;

        $trustedKeys = file_exists($storagePath)
            ? json_decode(file_get_contents($storagePath), true, 512, JSON_THROW_ON_ERROR)
            : [];

        $this->collection = new TrustedKeyCollection($trustedKeys);
    }

    public function add(string ... $keys) : void
    {
        $this->collection->add(... $keys);

        $this->save();
    }

    public function remove(string $key) : void
    {
        $this->collection->remove($key);
        $this->save();
    }

    public function contains(string $key) : bool
    {
        return $this->collection->contains($key);
    }

    public function toArray() : array
    {
        return $this->collection->toArray();
    }

    private function save() : void
    {
        file_put_contents($this->storagePath, json_encode($this->toArray(), JSON_THROW_ON_ERROR));
    }
}
