<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Generator;
use Phpcq\RepositoryDefinition\Exception\ToolNotFoundException;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;

class InstalledPlugin
{
    /**
     * @var array<string,ToolVersionInterface>
     */
    private array $tools = [];

    /**
     * @param list<ToolVersionInterface> $tools
     * @param array<string,string>       $composerPackages Installed versions of the explicitly required composer
     *                                                     packages.
     */
    public function __construct(
        private readonly PluginVersionInterface $version,
        array $tools = [],
        private ?string $composerLock = null,
        private array $composerPackages = []
    ) {
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
     * @return Generator<ToolVersionInterface>
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

    public function getComposerLock(): ?string
    {
        return $this->composerLock;
    }

    public function updateComposerLock(?string $composerLock): void
    {
        $this->composerLock = $composerLock;
    }

    /** @return array<string,string> */
    public function getComposerPackages(): array
    {
        return $this->composerPackages;
    }

    /** @param array<string,string> $composerPackages */
    public function updateComposerPackages(array $composerPackages): void
    {
        $this->composerPackages = $composerPackages;
    }
}
