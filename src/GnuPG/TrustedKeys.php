<?php

declare(strict_types=1);

namespace Phpcq\GnuPG;

use Phpcq\GnuPG\TrustedKeys\TrustedKeyCollectionInterface;
use Phpcq\GnuPG\TrustedKeys\TrustedKeyStorage;
use function array_unique;
use function array_values;

final class TrustedKeys implements TrustedKeyCollectionInterface
{
    /** @var TrustedKeyStorage */
    private $storage;

    /** @var TrustedKeyCollectionInterface */
    private $collection;

    /**
     * TrustedKeys constructor.
     *
     * @param TrustedKeyStorage             $storage
     * @param TrustedKeyCollectionInterface $collection
     */
    public function __construct(TrustedKeyStorage $storage, TrustedKeyCollectionInterface $collection)
    {
        $this->storage    = $storage;
        $this->collection = $collection;
    }

    public function add(string ...$keys) : void
    {
        $this->storage->add(... $keys);
    }

    public function remove(string $key) : void
    {
        $this->storage->remove($key);
        $this->collection->remove($key);
    }

    public function contains(string $key) : bool
    {
        if ($this->storage->contains($key)) {
            return true;
        }

        return $this->collection->contains($key);
    }

    public function toArray() : array
    {
        return array_values(array_unique(array_merge($this->storage->toArray(), $this->collection->toArray())));
    }
}
