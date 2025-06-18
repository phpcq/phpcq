<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Builder;

use Phpcq\Runner\Config\Validation\Constraints;
use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Configuration\Builder\BoolOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\EnumOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\FloatOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\IntOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\PrototypeBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\StringListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\StringOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;

use function array_splice;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @extends AbstractOptionBuilder<PrototypeBuilderInterface, array<string,mixed>>
 */
final class PrototypeOptionBuilder extends AbstractOptionBuilder implements PrototypeBuilderInterface
{
    use TypeTrait;

    /**
     * @psalm-suppress PropertyNotSetInConstructor selfValidate() checks it.
     * @var ConfigOptionBuilderInterface
     */
    private $valueBuilder;

    #[\Override]
    public function ofOptionsValue(): OptionsBuilderInterface
    {
        $this->declareType('array');

        return $this->valueBuilder = new OptionsBuilder($this->name, $this->description);
    }

    #[\Override]
    public function ofBoolValue(): BoolOptionBuilderInterface
    {
        $this->declareType('bool');

        return $this->valueBuilder = new BoolOptionBuilder($this->name, $this->description);
    }

    #[\Override]
    public function ofEnumValue(): EnumOptionBuilderInterface
    {
        $this->declareType('enum');

        return $this->valueBuilder = new EnumOptionBuilder($this->name, $this->description);
    }

    #[\Override]
    public function ofFloatValue(): FloatOptionBuilderInterface
    {
        $this->declareType('float');

        return $this->valueBuilder = new FloatOptionBuilder($this->name, $this->description);
    }

    #[\Override]
    public function ofIntValue(): IntOptionBuilderInterface
    {
        $this->declareType('int');

        return $this->valueBuilder = new IntOptionBuilder($this->name, $this->description);
    }

    #[\Override]
    public function ofStringListValue(): StringListOptionBuilderInterface
    {
        $this->declareType('string-list');

        return $this->valueBuilder = new StringListOptionBuilder($this->name, $this->description);
    }

    #[\Override]
    public function ofOptionsListValue(): OptionsListOptionBuilderInterface
    {
        $this->declareType('option-list');

        return $this->valueBuilder = new OptionsListOptionBuilder($this->name, $this->description);
    }

    #[\Override]
    public function ofStringValue(): StringOptionBuilderInterface
    {
        $this->declareType('string');

        return $this->valueBuilder = new StringOptionBuilder($this->name, $this->description);
    }

    #[\Override]
    public function ofPrototypeValue(): PrototypeBuilderInterface
    {
        $this->declareType('prototype');

        return $this->valueBuilder = new self($this->name, $this->description);
    }

    #[\Override]
    public function isRequired(): PrototypeBuilderInterface
    {
        return parent::isRequired();
    }

    #[\Override]
    public function withNormalizer(callable $normalizer): PrototypeBuilderInterface
    {
        return parent::withNormalizer($normalizer);
    }

    #[\Override]
    public function withValidator(callable $validator): PrototypeBuilderInterface
    {
        return parent::withValidator($validator);
    }

    /** @param array<string, mixed> $defaultValue */
    #[\Override]
    public function withDefaultValue(array $defaultValue): PrototypeBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    #[\Override]
    public function selfValidate(): void
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (null === $this->valueBuilder) {
            throw new RuntimeException('Prototype value type has to be defined');
        }

        $this->valueBuilder->selfValidate();
    }

    #[\Override]
    public function normalizeValue($raw): ?array
    {
        /** @psalm-suppress MixedAssignment */
        $raw = parent::normalizeValue($raw);
        if ($raw === null) {
            if ($this->required) {
                throw new InvalidConfigurationException(sprintf('Configuration key "%s" has to be set', $this->name));
            }

            return null;
        }

        $raw = Constraints::arrayConstraint($raw);
        if ($this->required && count($raw) === 0) {
            throw new InvalidConfigurationException(sprintf('Configuration key "%s" has to be set', $this->name));
        }

        /**
         * @var array<string, mixed> $raw
         * @psalm-suppress MixedAssignment
         */
        foreach ($raw as $key => $value) {
            /** @psalm-suppress MixedAssignment */
            $raw[$key] = $this->valueBuilder->normalizeValue($value);
        }

        return $raw;
    }

    #[\Override]
    public function validateValue($value): void
    {
        parent::validateValue($value);
        if (null === $value) {
            return;
        }

        try {
            $value = Constraints::arrayConstraint($value);
        } catch (InvalidConfigurationException $exception) {
            throw ConfigurationValidationErrorException::fromError([$this->name], $exception);
        }

        /** @psalm-suppress MixedAssignment */
        foreach ($value as $index => $option) {
            try {
                $this->valueBuilder->validateValue($option);
            } catch (ConfigurationValidationErrorException $exception) {
                $path = $exception->getPath();
                array_splice($path, 0, 1, [(string) $index]);
                throw ConfigurationValidationErrorException::fromError($path, $exception);
            } catch (InvalidConfigurationException $exception) {
                throw ConfigurationValidationErrorException::fromError(
                    [(string) $index],
                    $exception
                );
            }
        }
    }
}
