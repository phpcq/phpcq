<?php

declare(strict_types=1);

namespace Phpcq\Config;

use Phpcq\Config\Builder\OptionsBuilder;
use Phpcq\PluginApi\Version10\Configuration\Builder\ListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsBuilderInterface;

final class PhpcqConfigurationBuilder
{
    /** @var OptionsBuilder */
    private $builder;

    public function __construct()
    {
        $this->builder = new OptionsBuilder('phpcq', 'PHPCQ configuration');
        $this->builder
            ->describeListOption('directories', 'Directories which are checked by default')
            ->withDefaultValue([])
            ->isRequired()
            ->ofStringItems();
        $this->builder
            ->describeStringOption('artifact', 'Artifact directory for builds')
            ->isRequired()
            ->withDefaultValue('.phpcq/build');
        $this->describeRepositories($this->builder->describeListOption('repositories', 'Repositories'));

        $this->describeTools($this->builder->describePrototypeOption('tools', 'List of required plugins')->ofOptionsValue());

        $this->builder
            ->describeListOption('trusted-keys', 'List of trusted key fingerprints')
                ->withDefaultValue([])
                ->ofStringItems();

        $arrayBuilder = $this->builder
            ->describePrototypeOption('chains', 'Available chains. Default chain is required')
            ->withDefaultValue([])
            ->ofPrototypeValue()
                ->ofOptionsValue();
        assert($arrayBuilder instanceof OptionsBuilder);
        $arrayBuilder->bypassValueValidation();
    }

    public function processConfig(array $raw): array
    {
        $this->builder->selfValidate();
        $processed = $this->builder->normalizeValue($raw);
        $this->builder->validateValue($processed);

        return $processed;
    }

    private function describeRepositories(ListOptionBuilderInterface $builder): void
    {
        $builder->withDefaultValue([]);
        $itemsBuilder = $builder->ofOptionsItems();
        $itemsBuilder->withNormalizer(static function ($value): array {
            if (is_string($value)) {
                return [
                    'type' => 'remote',
                    'url'  => $value
                ];
            }

            return $value;
        });

        $itemsBuilder
            ->describeEnumOption('type', 'The type option')
            ->ofStringValues('local', 'remote')
            ->withDefaultValue('remote');

        $itemsBuilder
            ->describeStringOption('url', 'The url of a remote repository');
    }

    private function describeTools(OptionsBuilderInterface $builder): void
    {
        $builder->describeStringOption('version', 'Version constraint');
        // TODO: Check if we need a version for local tools
        //                        ->isRequired()
        $builder->describeStringOption('runner-plugin', 'Url to the bootstrap file. Use it to override default bootstrap');
        $builder
            ->describeBoolOption('signed', 'If set to false no signature verification happens')
            ->isRequired()
            ->withDefaultValue(true);
    }
}
