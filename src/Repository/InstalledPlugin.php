<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Generator;
use Phpcq\RepositoryDefinition\Exception\ToolNotFoundException;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;

class InstalledPlugin
{
    /** @var PluginVersionInterface */
    private $version;

    /**
     * @var array|ToolVersionInterface[]
     *
     * @psalm-var array<string,ToolVersionInterface>
     */
    private $tools = [];

    /**
     * @param array|ToolVersionInterface[] $tools
     *
     * @psalm-param list<ToolVersionInterface> $tools
     */
    public function __construct(PluginVersionInterface $version, array $tools = [])
    {
        $this->version = $version;
        foreach ($tools as $tool) {
            $this->tools[$tool->getName()] = $tool;
        }
    }

    public function getName(): string
    {
        return $this->getPluginVersion()->getName();
    }

    public function getPluginVersion(): PluginVersionInterface
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

    public function addTool(ToolVersionInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
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

    public function hasTool(string $name): bool
    {
        return isset($this->tools[$name]);
    }
}
