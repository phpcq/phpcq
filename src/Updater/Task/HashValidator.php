<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task;

use Phpcq\RepositoryDefinition\AbstractHash;
use Phpcq\Runner\Exception\RuntimeException;

trait HashValidator
{
    protected function validateHash(string $filePath, ?AbstractHash $hash): void
    {
        if (null === $hash) {
            return;
        }

        if (!$hash->equals($hash::createForFile($filePath, $hash->getType()))) {
            throw new RuntimeException('Invalid hash for file: ' . $filePath);
        }
    }
}
