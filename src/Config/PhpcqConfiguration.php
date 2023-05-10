<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config;

use Phpcq\PluginApi\Version10\Configuration\OptionsInterface;
use Phpcq\Runner\Exception\InvalidArgumentException;

/**
 * @psalm-type TPlugin = array{
 *    version: string,
 *    signed: bool,
 *    requirements?: array<string,array{version?: string, signed?: bool}
 * }
 * @psalm-type TTaskConfig = array{
 *   directories?: list<string>,
 *   plugin?: string,
 *   config: array<string, mixed>,
 *   uses?: array<string, array<string,mixed>|null>
 * }
 * @psalm-type TRepository = array{
 *   type: string,
 *   url?: string
 * }
 * @psalm-type TComposerConfig = array{
 *   autodiscover: bool
 * }
 * @psalm-type TConfig = array{
 *   repositories: list<string>,
 *   directories: list<string>,
 *   artifact: string,
 *   plugins: array<string,TPlugin>,
 *   trusted-keys: list<string>,
 *   tasks: array<string,TTaskConfig>,
 *   auth: array,
 *   composer: TComposerConfig
 * }
 */
final class PhpcqConfiguration
{
    /** @var OptionsInterface */
    private $options;

    public function __construct(OptionsInterface $options)
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

    /** @psalm-return array<string,TPlugin> */
    public function getPlugins(): array
    {
        return $this->options->getOptions('plugins');
    }

    /** @psalm-return list<TRepository> */
    public function getRepositories(): array
    {
        return $this->options->getOptionsList('repositories');
    }

    /** @psalm-return array<string,TTaskConfig> */
    public function getTaskConfig(): array
    {
        return $this->options->getOptions('tasks');
    }

    /** @psalm-return TTaskConfig */
    public function getConfigForTask(string $name): array
    {
        $config = $this->getTaskConfig();
        if (!array_key_exists($name, $config)) {
            if (array_key_exists($name, $this->getPlugins())) {
                return [];
            }
            throw new InvalidArgumentException(sprintf('Unknown task name "%s"', $name));
        }

        return $config[$name];
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
     * Get the composer configuration
     *
     * @return array<string,mixed>
     * @psalm-return TComposerConfig
     */
    public function getComposer(): array
    {
        return $this->options->getOptions('composer');
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
