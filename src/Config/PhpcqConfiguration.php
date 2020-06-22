<?php

declare(strict_types=1);

namespace Phpcq\Config;

final class PhpcqConfiguration
{
    /** @var Options */
    private $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

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
