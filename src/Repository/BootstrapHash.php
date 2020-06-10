<?php

declare(strict_types=1);

namespace Phpcq\Repository;

class BootstrapHash extends AbstractHash
{
    public function equals(BootstrapHash $other): bool
    {
        if ($this->getType() !== $other->getType()) {
            return false;
        }

        return $this->getValue() === $other->getValue();
    }
}
