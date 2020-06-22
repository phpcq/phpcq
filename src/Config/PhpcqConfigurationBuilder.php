<?php

declare(strict_types=1);

namespace Phpcq\Config;

use Phpcq\Config\Builder\OptionsBuilder;
use Phpcq\Config\Validation\Constraints;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsListOptionBuilderInterface;
use function is_array;

final class PhpcqConfigurationBuilder
{
    /** @var OptionsBuilder */
    private $builder;

    public function __construct()
    {
        $this->builder = new OptionsBuilder('phpcq', 'PHPCQ configuration');
        $this->builder
            ->describeStringListOption('directories', 'Directories which are checked by default')
            ->withDefaultValue([])
            ->isRequired();
        $this->builder
            ->describeStringOption('artifact', 'Artifact directory for builds')
            ->withDefaultValue('.phpcq/build')
            ->isRequired();
        $this->describeRepositories($this->builder->describeOptionsListOption('repositories', 'Repositories'));

        $this->describeTools(
            $this->builder->describePrototypeOption('tools', 'List of required plugins')->ofOptionsValue()
        );

        $this->builder
            ->describeStringListOption('trusted-keys', 'List of trusted key fingerprints')
            ->withDefaultValue([])
            ->isRequired();

        $arrayBuilder = $this->builder
            ->describePrototypeOption('chains', 'Available chains. Default chain is required')
            ->withDefaultValue([])
            ->ofPrototypeValue()
                ->ofOptionsValue();
        assert($arrayBuilder instanceof OptionsBuilder);
        /** @psalm-suppress DeprecatedMethod */
        $arrayBuilder->bypassValueValidation();
    }

    /** @psalm-return array<string,mixed> */
    public function processConfig(array $raw): array
    {
        $this->builder->selfValidate();
        $processed = $this->builder->normalizeValue($raw);
        $this->builder->validateValue($processed);

        /** @psalm-var array<string,mixed> $processed */
        return $processed;
    }

    private function describeRepositories(OptionsListOptionBuilderInterface $builder): void
    {
        $builder
            ->withDefaultValue([])
            ->isRequired()
            ->withNormalizer(static function ($value): array {
                if (is_string($value)) {
                    return [
                        'type' => 'remote',
                        'url'  => $value
                    ];
                }

                return Constraints::arrayConstraint($value);
            });

        $builder
            ->describeEnumOption('type', 'The type option')
            ->ofStringValues('local', 'remote')
            ->withDefaultValue('remote');

        $builder
            ->describeStringOption('url', 'The url of a remote repository');
    }

    private function describeTools(OptionsBuilderInterface $builder): void
    {
        $builder->describeStringOption('version', 'Version constraint');
        // TODO: Check if we need a version for local tools
        //                        ->isRequired()
        $builder->describeStringOption(
            'runner-plugin',
            'Url to the bootstrap file. Use it to override default bootstrap'
        );
        $builder
            ->describeBoolOption('signed', 'If set to false no signature verification happens')
            ->withDefaultValue(true)
            ->isRequired();
    }
}
