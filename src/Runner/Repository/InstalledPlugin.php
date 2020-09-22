<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Generator;
use Phpcq\RepositoryDefinition\Exception\ToolNotFoundException;
use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;

final class InstalledPlugin
{
    /** @var PhpFilePluginVersionInterface */
    private $version;

    /**
     * @var array|ToolVersionInterface[]
     *
     * @psalm-var array<string,ToolVersionInterface>
     */
    private $tools;

    /**
     * @param array|ToolVersionInterface[] $tools
     *
     * @psalm-param array<string,ToolVersionInterface> $tools
     */
    public function __construct(PhpFilePluginVersionInterface $version, array $tools)
    {
        $this->version = $version;
        $this->tools   = $tools;
    }

    public function getName(): string
    {
        return $this->getPluginVersion()->getName();
    }

    public function getPluginVersion(): PhpFilePluginVersionInterface
    {
        return $this->version;
    }

    public function getTool(string $name): ToolVersionInterface
    {
        if (!isset($this->tools[$name])) {
            throw new ToolNotFoundException($name);
        }

        return $this->tools[$name];
    }

    /**
     * Iterate over all installed tools.
     *
     * @return Generator|ToolVersionInterface[]
     *
     * @psalm-return Generator<ToolVersionInterface>
     */
    public function iterateTools(): Generator
    {
        foreach ($this->tools as $toolVersion) {
            yield $toolVersion;
        }
    }
}
