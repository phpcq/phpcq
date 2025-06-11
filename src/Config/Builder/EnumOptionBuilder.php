<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Builder;

use Phpcq\Runner\Config\Validation\Validator;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Configuration\Builder\EnumOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\FloatOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\IntOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\StringOptionBuilderInterface;

/**
 * @extends AbstractOptionBuilder<EnumOptionBuilderInterface, int|float|string>
 */
final class EnumOptionBuilder extends AbstractOptionBuilder implements EnumOptionBuilderInterface
{
    use TypeTrait;

    /**
     * @psalm-suppress PropertyNotSetInConstructor selfValidate() checks it.
     * @var ConfigOptionBuilderInterface
     */
    private $valueBuilder;

    #[\Override]
    public function isRequired(): EnumOptionBuilderInterface
    {
        return parent::isRequired();
    }

    #[\Override]
    public function withNormalizer(callable $normalizer): EnumOptionBuilderInterface
    {
        return parent::withNormalizer($normalizer);
    }

    #[\Override]
    public function withValidator(callable $validator): EnumOptionBuilderInterface
    {
        return parent::withValidator($validator);
    }

    #[\Override]
    public function ofStringValues(string ...$values): StringOptionBuilderInterface
    {
        /** @var list<string> $values */
        $this->declareType('string');
        $this
            ->withValidator(Validator::stringValidator())
            ->withValidator(Validator::enumValidator($values));

        return $this->valueBuilder = new StringOptionBuilder($this->name, $this->description);
    }

    #[\Override]
    public function ofIntValues(int ...$values): IntOptionBuilderInterface
    {
        /** @var list<int> $values */
        $this->declareType('int');
        $this
            ->withValidator(Validator::intValidator())
            ->withValidator(Validator::enumValidator($values));

        return $this->valueBuilder = new IntOptionBuilder($this->name, $this->description);
    }

    #[\Override]
    public function ofFloatValues(float ...$values): FloatOptionBuilderInterface
    {
        /** @var list<float> $values */
        $this->declareType('float');
        $this
            ->withValidator(Validator::floatValidator())
            ->withValidator(Validator::enumValidator($values));

        return $this->valueBuilder = new FloatOptionBuilder($this->name, $this->description);
    }

    #[\Override]
    public function normalizeValue($raw)
    {
        /** @psalm-suppress MixedAssignment */
        $raw = $this->valueBuilder->normalizeValue($raw);

        return parent::normalizeValue($raw);
    }

    #[\Override]
    public function validateValue($value): void
    {
        parent::validateValue($value);

        $this->valueBuilder->normalizeValue($value);
    }

    #[\Override]
    public function selfValidate(): void
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (null === $this->valueBuilder) {
            throw new RuntimeException('Enum value type has to be defined');
        }
    }
}
