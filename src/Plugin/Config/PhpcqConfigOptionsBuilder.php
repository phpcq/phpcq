<?php

declare(strict_types=1);

namespace Phpcq\Plugin\Config;

use Phpcq\Exception\InvalidConfigException;
use function array_values;

final class PhpcqConfigOptionsBuilder implements ConfigOptionsBuilderInterface
{
    /** @var array<string, AbstractConfigOption>|AbstractConfigOption[] */
    private $options;

    public function describeArrayOption(
        string $name,
        string $description,
        array $defaultValue
    ) : ConfigOptionsBuilderInterface {
        return $this->describeOption(new ArrayConfigOption($name, $description, $defaultValue));
    }

    public function describeIntOption(
        string $name,
        string $description,
        int $defaultValue
    ) : ConfigOptionsBuilderInterface {
        return $this->describeOption(new IntConfigOption($name, $description, $defaultValue));
    }

    public function describeStringOption(
        string $name,
        string $description,
        string $defaultValue
    ) : ConfigOptionsBuilderInterface {
        return $this->describeOption(new StringConfigOption($name, $description, $defaultValue));
    }

    public function describeBoolOption(
        string $name,
        string $description,
        bool $defaultValue
    ) : ConfigOptionsBuilderInterface {
        return $this->describeOption(new BoolConfigOption($name, $description, $defaultValue));
    }

    public function describeOption(ConfigOptionInterface $configOption) : ConfigOptionsBuilderInterface
    {
        $this->options[$configOption->getName()] = $configOption;

        return $this;
    }

    public function validateConfig(array $config): void
    {
        if ($diff = array_diff_key($config, $this->options)) {
            throw new InvalidConfigException(
                'Unknown config keys encountered: ' . implode(', ', array_keys($diff))
            );
        }

        foreach ($this->options as $option) {
            $option->validateValue($config[$option->getName()]);
        }
    }

    /**
     * @return ConfigOptionInterface[]
     */
    public function getOptions(): iterable
    {
        return array_values($this->options);
    }
}
