<?php

declare(strict_types=1);

namespace Phpcq\Plugin\Config;

use Phpcq\Exception\InvalidConfigException;

interface ConfigOptionsBuilderInterface
{
    public function describeArrayOption(string $name, string $description, ?array $defaultValue = null): self;

    public function describeIntOption(string $name, string $description, ?int $defaultValue = null): self;

    public function describeStringOption(string $name, string $description, ?string $defaultValue = null): self;

    public function describeBoolOption(string $name, string $description, ?bool $defaultValue = null): self;

    public function describeOption(ConfigOptionInterface $configOption) : self;

    /**
     * @param array $config
     *
     * @throws InvalidConfigException When configuration is not valid.
     */
    public function validateConfig(array $config): void;

    /**
     * @return AbstractConfigOption[]
     */
    public function getOptions(): iterable;
}
