<?php

declare(strict_types=1);

namespace Phpcq\Config;

use Phpcq\Config\Builder\ArrayOptionBuilder;
use Phpcq\Config\Builder\RootOptionsBuilder;

final class PhpcqConfigurationBuilder
{
    /** @var ArrayOptionBuilder */
    private $builder;

    public function __construct()
    {
        $this->builder = new RootOptionsBuilder('phpcq', 'PHPCQ configuration');
        $this->builder
            ->describeListOption('directories', 'Directories which are checked by default')
                ->ofStringItems()
            ->end()
            ->describeStringOption('artifact', 'Artifact directory for builds')
                ->withDefaultValue('.phpcq/build')
            ->end()
            ->describeListOption('repositories', 'Artifact directory for builds')
                ->ofArrayItems()
                    ->withNormalizer(static function ($value): array {
                        if (is_string($value)) {
                            return [
                                'type' => 'remote',
                                'url'  => $value
                            ];
                        }

                        return $value;
                    })
                    ->describeEnumOption('type', 'The type option')
                        ->ofStringValues('local', 'remote')
                        ->withDefaultValue('remote')
                    ->end()
                    ->describeStringOption('url', 'The url of a remote repository')
                    ->end()
                    // Fixme: Describe local repository
                ->end()
            ->end()
            ->describePrototypeOption('tools', 'List of required plugins')
                ->ofArrayValue()
                    ->describeStringOption('version', 'Version constraint')
// TODO: Check if we need a version for local tools
//                        ->isRequired()
                    ->end()
                    ->describeStringOption('runner-plugin', 'Url to the bootstrap file. Use it to override default bootstrap')
                    ->end()
                    ->describeBoolOption('signed', 'If set to false no signature verification happens')
                        ->withDefaultValue(true)
                    ->end()
                ->end()
            ->end()
            ->describeListOption('trusted-keys', 'List of trusted key fingerprints')
                ->ofStringItems()
            ->end()
            ->describePrototypeOption('chains', 'Available chains. Default chain is required')
                ->ofPrototypeValue()
                    ->ofArrayValue()
                        ->describePrototypeOption('directories', 'Directories being processed')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    public function processConfig(array $raw): PhpcqConfiguration
    {
        return new PhpcqConfiguration($this->builder->processConfig($raw));
    }
}
