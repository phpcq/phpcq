<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task;

use Phpcq\RepositoryDefinition\AbstractHash;
use Phpcq\RepositoryDefinition\Plugin\PluginHash;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\Runner\Exception\RuntimeException;

use function get_class;

trait HashValidator
{
    protected function validateHash(string $filePath, ?AbstractHash $hash): void
    {
        if (null === $hash) {
            return;
        }

        switch (true) {
            case $hash instanceof PluginHash:
                if (!$hash->equals(PluginHash::createForFile($filePath, $hash->getType()))) {
                    throw new RuntimeException('Invalid hash for file: ' . $filePath);
                }
                break;
            case $hash instanceof ToolHash:
                if (!$hash->equals(ToolHash::createForFile($filePath, $hash->getType()))) {
                    throw new RuntimeException('Invalid hash for file: ' . $filePath);
                }
                break;

            default:
                throw new RuntimeException('Unsupported hash: ' . $hash::class);
        }
    }
}
