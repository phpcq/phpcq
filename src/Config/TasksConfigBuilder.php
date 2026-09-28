<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config;

use Phpcq\Runner\Config\Builder\TaskConfigBuilder;
use Phpcq\Runner\Config\Validation\Constraints;
use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
use Throwable;

use function array_key_exists;
use function array_keys;

/**
 * Processes the task configurations.
 *
 * @psalm-import-type TTaskConfig from PhpcqConfiguration
 */
final class TasksConfigBuilder
{
    /**
     * @param mixed $raw The raw task configurations.
     *
     * @return array<string, TTaskConfig>
     *
     * @throws ConfigurationValidationErrorException When a task configuration is invalid.
     */
    public function processConfig($raw): array
    {
        try {
            $raw = Constraints::arrayConstraint($raw ?? []);
        } catch (Throwable $exception) {
            throw ConfigurationValidationErrorException::fromError(['tasks'], $exception);
        }

        $tasks = [];
        /** @psalm-suppress MixedAssignment */
        foreach ($raw as $taskName => $taskConfig) {
            $tasks[(string) $taskName] = $this->processTask((string) $taskName, $taskConfig);
        }

        // Define default task if not defined.
        if (!array_key_exists('default', $tasks)) {
            $tasks['default'] = [
                'plugin' => 'chain',
                'config' => [
                    'tasks' => array_keys($tasks),
                ],
            ];
        }

        return $tasks;
    }

    /**
     * @param mixed $raw
     *
     * @return TTaskConfig
     */
    private function processTask(string $taskName, $raw): array
    {
        $builder = new TaskConfigBuilder($taskName);

        try {
            $processed = $builder->normalizeValue($raw);
            $builder->validateValue($processed);
        } catch (ConfigurationValidationErrorException $exception) {
            throw $exception->withOuterPath(['tasks']);
        } catch (Throwable $exception) {
            throw ConfigurationValidationErrorException::fromError(['tasks', $taskName], $exception);
        }

        /** @var TTaskConfig $processed */
        return $processed;
    }
}
