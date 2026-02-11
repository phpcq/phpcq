<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Phpcq\Runner\Config\Builder\PluginConfigurationBuilder;
use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
use Phpcq\Runner\Plugin\PluginRegistry;
use Phpcq\PluginApi\Version10\ConfigurationPluginInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function array_key_exists;
use function md5;
use function serialize;
use function sprintf;

final class ValidateCommand extends AbstractCommand
{
    use InstalledRepositoryLoadingCommandTrait;

    #[\Override]
    protected function configure(): void
    {
        $this->setName('validate')->setDescription('Validate the phpcq installation');
        parent::configure();
    }

    #[\Override]
    protected function doExecute(): int
    {
        $this->output->writeln('Validate phpcq configuration', OutputInterface::VERBOSITY_VERY_VERBOSE);

        $installed  = $this->getInstalledRepository(true);
        $plugins    = PluginRegistry::buildFromInstalledRepository($installed);

        $valid = true;
        foreach (array_keys($this->config->getTaskConfig()) as $taskName) {
            if (!$this->validatePlugin($plugins, $taskName)) {
                $valid = false;
            }
        }

        return $valid ? 0 : 1;
    }

    /**
     * Validate a plugin.
     *
     * It validates a plugin configuration, creates console output and returns boolean to indicate valid configuration.
     *
     * @param PluginRegistry $plugins  The plugin registry.
     * @param string         $taskName The task being validated.
     *
     * @return bool
     *
     * @psalm-suppress UnusedVariable
     */
    private function validatePlugin(PluginRegistry $plugins, string $taskName): bool
    {
        /** @var array<string,array<string,bool>> */
        static $cache = [];

        $configValues = $this->config->getConfigForTask($taskName);
        $plugin = $plugins->getPluginByName($configValues['plugin'] ?? $taskName);

        if (!$plugin instanceof ConfigurationPluginInterface) {
            return true;
        }

        $configOptionsBuilder = new PluginConfigurationBuilder($plugin->getName(), 'Plugin configuration');
        $pluginConfig = $configValues['config'] ?? [];

        $hash = md5($taskName . serialize($pluginConfig));
        if (isset($cache[$taskName]) && array_key_exists($hash, $cache[$taskName])) {
            $this->output->writeln(
                sprintf(' - %s: <info>configuration already validated</info>', $taskName),
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            return $cache[$taskName][$hash];
        }

        $plugin->describeConfiguration($configOptionsBuilder);

        try {
            /** @var array<string,mixed> $processed */
            $processed = $configOptionsBuilder->normalizeValue($pluginConfig);
            $configOptionsBuilder->validateValue($processed);

            $this->output->writeln(
                sprintf(' - %s: <info>valid configuration</info>', $taskName),
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            return $cache[$taskName][$hash] = true;
        } catch (ConfigurationValidationErrorException $exception) {
            $exception = $exception->withOuterPath(['tasks', $taskName, 'config']);
        } catch (Throwable $exception) {
            $exception = ConfigurationValidationErrorException::fromError(['tasks', $taskName, 'config'], $exception);
        }
        $this->output->writeln(
            sprintf(' - %s: <error>%s</error>', $taskName, $exception->getMessage()),
            OutputInterface::VERBOSITY_VERBOSE
        );

        return $cache[$taskName][$hash] = false;
    }
}
