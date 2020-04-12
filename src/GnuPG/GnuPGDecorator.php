<?php

declare(strict_types=1);

namespace Phpcq\GnuPG;

use Gnupg;
use Phpcq\Exception\GnuPGException;

final class GnuPGDecorator implements GnuPGInterface
{
    /** @var Gnupg */
    private $inner;

    /**
     * GnuPGDecorator constructor.
     *
     * @param Gnupg $inner
     */
    public function __construct(Gnupg $inner)
    {
        $this->inner = $inner;
    }

    /** @inheritDoc */
    public function import(string $key) : array
    {
        $result = $this->inner->import($key);

        if ($result['imported'] === 0) {
            throw new GnuPGException('importing key "%s" failed');
        }

        return $result;
    }

    /** @inheritDoc */
    public function keyinfo(string $search) : array
    {
        return $this->inner->keyinfo($search);
    }

    /** @inheritDoc */
    public function verify(string $message, string $signature)
    {
        return $this->inner->verify($message, $signature);
    }
}
