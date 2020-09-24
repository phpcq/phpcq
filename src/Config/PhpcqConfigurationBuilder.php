<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config;

use Composer\Semver\VersionParser;
use Phpcq\Runner\Config\Builder\OptionsBuilder;
use Phpcq\Runner\Config\Validation\Constraints;
use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsListOptionBuilderInterface;
use Throwable;

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

        $this->describePlugins(
            $this->builder->describePrototypeOption('plugins', 'List of required plugins')->ofOptionsValue()
        );

        $this->builder
            ->describeStringListOption('trusted-keys', 'List of trusted key fingerprints')
            ->withDefaultValue([])
            ->isRequired();

        $this->builder
            ->describePrototypeOption('auth', 'Authentication configuration. Not defined yet.')
            ->withDefaultValue([])
            ->ofOptionsValue();

        $this->builder
            ->describePrototypeOption('chains', 'Available chains. Default chain is required')
            ->withDefaultValue([])
            ->ofPrototypeValue()
                ->ofStringValue();
    }

    /** @psalm-return array<string,mixed> */
    public function processConfig(array $raw): array
    {
        $this->builder->selfValidate();

        try {
            $processed = $this->builder->normalizeValue($raw);
            $this->builder->validateValue($processed);
        } catch (ConfigurationValidationErrorException $exception) {
            throw $exception->withOuterPath(['phpcq']);
        } catch (Throwable $exception) {
            throw ConfigurationValidationErrorException::fromError(['phpcq'], $exception);
        }

        /** @psalm-var array<string,mixed> $processed */
        return $processed;
    }

    private function describeRepositories(OptionsListOptionBuilderInterface $builder): void
    {
        $builder
            ->withDefaultValue([])
            ->isRequired()
            ->withNormalizer(/** @param mixed $value */ static function ($value): array {
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

    private function describePlugins(OptionsBuilderInterface $builder): void
    {
        $validateConstraint = static function (string $constraint): void {
            $versionParser = new VersionParser();
            $versionParser->parseConstraints($constraint);
        };
        $builder
            ->describeStringOption('version', 'Version constraint')
            ->withValidator($validateConstraint)
            ->isRequired()
            ->withDefaultValue('*');

        $builder->describeStringOption(
            'runner-plugin',
            'Url to the plugin file. Use it to override default plugin'
        );
        $builder
            ->describeBoolOption('signed', 'If set to false no signature verification happens')
            ->withDefaultValue(true)
            ->isRequired();

        $requirements = $builder
            ->describePrototypeOption('requirements', 'Override the tool requirements of the plugin')
            ->ofOptionsValue();
        $requirements
                ->describeStringOption('version', 'The version constraint')
                ->withValidator($validateConstraint);
        $requirements
            ->describeBoolOption('signed', 'If set to false no signature verification happens')
            ->withDefaultValue(true)
            ->isRequired();
    }
}
