<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Builder;

use Phpcq\PluginApi\Version10\FixStage;
use Phpcq\Runner\Config\Validation\Constraints;

use function array_map;
use function array_values;
use function is_array;

/**
 * Describes the configuration of a single task.
 *
 * The plugin configuration ("config") and the enricher configurations ("uses") are only validated to be arrays as
 * they can only be validated as soon as the plugins are loaded.
 *
 * @extends AbstractOptionsBuilder<array<string,mixed>>
 */
final class TaskConfigBuilder extends AbstractOptionsBuilder
{
    public function __construct(string $name)
    {
        parent::__construct($name, 'Task configuration');

        $this->withNormalizer(
            /**
             * @param mixed $value
             * @return mixed
             */
            static function ($value) {
                if (null === $value) {
                    return [];
                }

                // Support simplified chain plugin configuration.
                if (is_array($value) && [] !== $value && $value === array_values($value)) {
                    return [
                        'plugin' => 'chain',
                        'config' => ['tasks' => $value],
                    ];
                }

                return $value;
            }
        );

        $this->describeStringOption('plugin', 'Name of the plugin. Defaults to the task name');
        $this->describeStringListOption(
            'directories',
            'Directories processed by the task. Defaults to the configured directories'
        );
        $this->describeEnumOption('fix-stage', 'Overrides the fix stage of the plugin')
            ->ofStringValues(...array_map(static fn (FixStage $stage): string => $stage->value, FixStage::cases()));
        $this->describeOption(
            'config',
            (new ArrayOptionBuilder('config', 'Plugin configuration, validated by the plugin'))->withDefaultValue([])
        );
        $this->describeOption(
            'uses',
            (new ArrayOptionBuilder('uses', 'Enricher configurations by enricher name, validated by the enrichers'))
                ->withValidator(
                    /** @param mixed $value */
                    static function ($value): void {
                        /** @psalm-suppress MixedAssignment */
                        foreach (Constraints::arrayConstraint($value) as $enricherConfig) {
                            if (null !== $enricherConfig) {
                                Constraints::arrayConstraint($enricherConfig);
                            }
                        }
                    }
                )
        );
    }
}
