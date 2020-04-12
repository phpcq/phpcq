<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\TrustedKeys;

use function array_merge;
use function array_search;
use function array_unique;
use function array_values;
use function in_array;

final class TrustedKeyCollection implements TrustedKeyCollectionInterface
{
    /**
     * @psalm-var list<string>
     *
     * @var array|string[]
     */
    private $trustedKeys;

    /**
     * TrustedKeyCollection constructor.
     *
     * @psalm-param list<string> $trustedKeys
     *
     * @param string[] $trustedKeys
     */
    public function __construct(array $trustedKeys = [])
    {
        $this->trustedKeys = $trustedKeys;
    }

    public function add(string ... $keys) : void
    {
        $this->trustedKeys = array_values(array_unique(array_merge($this->trustedKeys, $keys)));
    }

    public function remove(string $key) : void
    {
        $position = array_search($key, $this->trustedKeys, true);
        if (false === $position) {
            return;
        }

        unset($this->trustedKeys[$position]);
        $this->trustedKeys = array_values($this->trustedKeys);
    }

    public function contains(string $key) : bool
    {
        return in_array($key, $this->trustedKeys, true);
    }

    public function toArray() : array
    {
        return $this->trustedKeys;
    }
}
