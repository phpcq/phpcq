<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Builder;

use Phpcq\Runner\Config\Validation\Constraints;
use Phpcq\Runner\Config\Validation\Validator;
use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;

use function array_diff_key;
use function array_keys;
use function count;
use function sprintf;

/**
 * @psalm-template TReturnType
 * @extends AbstractOptionBuilder<OptionsBuilderInterface, array<string,mixed>>
 */
abstract class AbstractOptionsBuilder extends AbstractOptionBuilder implements OptionsBuilderInterface
{
    use OptionsBuilderTrait;

    public function __construct(string $name, string $description)
    {
        parent::__construct($name, $description, [Validator::arrayValidator()]);
    }

    public function isRequired(): OptionsBuilderInterface
    {
        return parent::isRequired();
    }

    public function withNormalizer(callable $normalizer): OptionsBuilderInterface
    {
        return parent::withNormalizer($normalizer);
    }

    public function withValidator(callable $validator): OptionsBuilderInterface
    {
        return parent::withValidator($validator);
    }

    public function withDefaultValue(array $defaultValue): OptionsBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function normalizeValue($raw): ?array
    {
        /** @psalm-suppress MixedAssignment */
        $value = parent::normalizeValue($raw);
        if ($value === null) {
            if ($this->required) {
                throw ConfigurationValidationErrorException::withCustomMessage(
                    [$this->name],
                    sprintf('Configuration key "%s" has to be set', $this->name)
                );
            }

            return null;
        }

        /** @psalm-var array<string, mixed> $value */
        $value = Constraints::arrayConstraint($value);

        return $this->normalizeOptions($value);
    }

    public function validateValue($options): void
    {
        parent::validateValue($options);

        /** @var array $options - We validate it withing parent validator */
        $diff = array_diff_key($options, $this->options);
        if (count($diff) > 0) {
            /** @psalm-var list<string> $keys */
            $keys = array_keys($diff);
            throw ConfigurationValidationErrorException::withCustomMessage(
                [$keys[0]],
                sprintf('Unexpected array key "%s"', $keys[0])
            );
        }

        foreach ($this->options as $key => $builder) {
            try {
                $builder->validateValue($options[$key] ?? null);
            } catch (ConfigurationValidationErrorException $exception) {
                throw $exception->withOuterPath([$key]);
            } catch (InvalidConfigurationException $exception) {
                throw ConfigurationValidationErrorException::fromError(
                    [$key],
                    $exception
                );
            }
        }
    }
}
