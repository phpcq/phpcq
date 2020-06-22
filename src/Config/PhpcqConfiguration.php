<?php

declare(strict_types=1);

namespace Phpcq\Config;

/**
 * @psalm-type TTool = array{
 *    version: string,
 *    signed: bool
 * }
 * @psalm-type TToolConfig = array{
 *   directories?: array<string, array|null|bool>
 * }
 * @psalm-type TRepository = array{
 *   type: string,
 *   url?: string
 * }
 * @psalm-type TConfig = array{
 *   directories: list<string>,
 *   artifact: string,
 *   trusted-keys: list<string>,
 *   chains: array<string,array<string,array|null>>,
 *   tools: array<string,TTool>,
 *   tool-config: array<string,TToolConfig>,
 *   repositories: list<int, string>,
 *   auth: array
 * }
 */
final class PhpcqConfiguration extends Options
{
    /**
     * @psalm-param TConfig
     */
    public static function fromArray(array $options): self
    {
        return new self($options);
    }

    /**
     * @return string[]
     * @psalm-return list<string>
     */
    public function getDirectories(): array
    {
        return $this->getStringList('directories');
    }

    public function getArtifactDir(): string
    {
        return $this->getString('artifact');
    }

    /** @psalm-return array<string,TTool> */
    public function getTools(): array
    {
        return $this->getOptions('tools')->getValue();
    }

    /** @psalm-return list<TRepository> */
    public function getRepositories(): array
    {
        return $this->getOptionsList('repositories');
    }

    /** @psalm-return array<string,array<string,array|null>> */
    public function getChains(): array
    {
        return $this->getOptions('chain')->getValue();
    }

    /** @psalm-return array<string,TToolConfig> */
    public function getToolConfig(): array
    {
        return $this->getOptions('tool-config')->getValue();
    }

    /*
     *  *   directories: list<string>,
 *   artifact: string,
 *   trusted-keys: list<string>,
 *   chains: array<string,array<string,array|null>>,
 *   tools: array<string,TTool>,
 *   tool-config: array<string,TToolConfig>,
 *   repositories: list<int, string>,
 *   auth: array
     */

}
