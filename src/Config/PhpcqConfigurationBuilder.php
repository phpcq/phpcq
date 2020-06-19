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
            ->describePrototype('tools', 'List of required plugins')
                ->ofArrayValue()
                    ->describeStringOption('version', 'Version constraint')
                        ->isRequired()
                    ->end()
                    ->describeStringOption('bootstrap', 'Url to the bootstrap file. Use it to override default bootstrap')
                    ->end()
                    ->describeBoolOption('signed', 'If set to false no signature verification happens')
                        ->withDefaultValue('false')
                    ->end()
                ->end()
            ->end()
            ->describeListOption('trusted-keys', 'List of trusted key fingerprints')
                ->ofStringItems()
            ->end()
            ->describePrototype('chains', 'Available chains. Default chain is required')
                ->ofListValue()
                    ->ofStringItems()
                    ->end()
            ->end();
    }

    public function processConfig(array $raw): PhpcqConfiguration
    {
        return new PhpcqConfiguration($this->builder->processConfig($raw));
    }
}