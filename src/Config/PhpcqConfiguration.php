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
final class PhpcqConfiguration
{
    /** @var Options */
    private $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    /**
     * @psalm-param TConfig
     */
    public static function fromArray(array $options): self
    {
        return new self(new Options($options));
    }

    /**
     * @return string[]
     * @psalm-return list<string>
     */
    public function getDirectories(): array
    {
        return $this->options->getStringList('directories');
    }

    public function getArtifactDir(): string
    {
        return $this->options->getString('artifact');
    }

    /** @psalm-return array<string,TTool> */
    public function getTools(): array
    {
        return $this->options->getOptions('tools');
    }

    /** @psalm-return list<TRepository> */
    public function getRepositories(): array
    {
        return $this->options->getOptionsList('repositories');
    }

    /** @psalm-return array<string,array<string,array|null>> */
    public function getChains(): array
    {
        return $this->options->getOptions('chains');
    }

    /** @psalm-return array<string,TToolConfig> */
    public function getToolConfig(): array
    {
        return $this->options->getOptions('tool-config');
    }

    /** @psalm-return list<string> */
    public function getTrustedKeys(): array
    {
        return $this->options->getStringList('trusted-keys');
    }

    /** @return array<string, mixed> */
    public function getAuth(): array
    {
        return $this->options->getOptions('auth');
    }

    /**
     * Get configuration as array.
     *
     * @return array
     * @psalm-return TConfig
     */
    public function asArray(): array
    {
        return $this->options->getValue();
    }
}
