<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Builder;

use Phpcq\Runner\Config\Validation\Validator;
use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
use Phpcq\PluginApi\Version10\Configuration\Builder\BoolOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\EnumOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\FloatOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\IntOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\PrototypeBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\StringListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\StringOptionBuilderInterface;
use Throwable;

use function array_key_exists;
use function assert;

trait OptionsBuilderTrait
{
    /**
     * @var ConfigOptionBuilderInterface[]
     * @var array<string, ConfigOptionBuilderInterface>
     */
    protected $options = [];

    public function describeOptions(string $name, string $description): OptionsBuilderInterface
    {
        $builder = new OptionsBuilder($name, $description);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeBoolOption(string $name, string $description): BoolOptionBuilderInterface
    {
        $builder = new BoolOptionBuilder($name, $description, [Validator::boolValidator()]);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeFloatOption(string $name, string $description): FloatOptionBuilderInterface
    {
        $builder = new FloatOptionBuilder($name, $description, [Validator::floatValidator()]);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeIntOption(string $name, string $description): IntOptionBuilderInterface
    {
        $builder = new IntOptionBuilder($name, $description, [Validator::intValidator()]);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describePrototypeOption(string $name, string $description): PrototypeBuilderInterface
    {
        $builder = new PrototypeOptionBuilder($name, $description);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeStringOption(string $name, string $description): StringOptionBuilderInterface
    {
        $builder = new StringOptionBuilder($name, $description, [Validator::stringValidator()]);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeStringListOption(string $name, string $description): StringListOptionBuilderInterface
    {
        $builder = new StringListOptionBuilder($name, $description);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeOptionsListOption(string $name, string $description): OptionsListOptionBuilderInterface
    {
        $builder = new OptionsListOptionBuilder($name, $description);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeEnumOption(string $name, string $description): EnumOptionBuilderInterface
    {
        $builder = new EnumOptionBuilder($name, $description);
        $this->describeOption($name, $builder);

        return $builder;
    }

    protected function describeOption(string $name, ConfigOptionBuilderInterface $builder): void
    {
        $this->options[$name] = $builder;
    }

    /**
     * @param array<string,mixed> $options
     *
     * @return array<string,mixed>
     */
    protected function normalizeOptions(array $options): array
    {
        foreach ($this->options as $key => $builder) {
            if (array_key_exists($key, $options) && $options[$key] === null) {
                unset($options[$key]);
                continue;
            }

            try {
                /** @psalm-suppress MixedAssignment */
                if (null === ($processed = $builder->normalizeValue($options[$key] ?? null))) {
                    unset($options[$key]);
                } else {
                    /** @psalm-suppress MixedAssignment */
                    $options[$key] = $processed;
                }
            } catch (ConfigurationValidationErrorException $exception) {
                throw $exception->withOuterPath([$this->name]);
            } catch (Throwable $exception) {
                throw ConfigurationValidationErrorException::fromError([$this->name, $key], $exception);
            }
        }

        return $options;
    }
}
