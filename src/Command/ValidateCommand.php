<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Plugin\Config\PhpcqConfigurationOptionsBuilder;
use Phpcq\Plugin\PluginRegistry;
use Phpcq\PluginApi\Version10\ConfigurationPluginInterface;
use Phpcq\PluginApi\Version10\InvalidConfigException;
use Symfony\Component\Console\Output\OutputInterface;

use function array_keys;
use function sprintf;

final class ValidateCommand extends AbstractCommand
{
    use InstalledRepositoryLoadingCommandTrait;

    protected function configure(): void
    {
        $this->setName('validate')->setDescription('Validate the phpcq installation');
        parent::configure();
    }

    protected function doExecute(): int
    {
        $this->output->writeln('Validate phpcq configuration', OutputInterface::VERBOSITY_VERY_VERBOSE);

        $installed  = $this->getInstalledRepository(true);
        $plugins    = PluginRegistry::buildFromInstalledRepository($installed);

        $this->output->writeln('Validate plugins:', OutputInterface::VERBOSITY_VERY_VERBOSE);

        $valid = true;
        foreach (array_keys($this->config['tools']) as $toolName) {
            if (!$this->validatePlugin($plugins, $toolName)) {
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
     * @param string         $toolName The tool name being validated.
     *
     * @return bool
     */
    protected function validatePlugin(PluginRegistry $plugins, string $toolName): bool
    {
        $plugin = $plugins->getPluginByName($toolName);
        $name   = $plugin->getName();

        if (! $plugin instanceof ConfigurationPluginInterface) {
            return true;
        }

        $configOptionsBuilder = new PhpcqConfigurationOptionsBuilder();
        $configuration        = $this->config[$name] ?? [];

        $plugin->describeOptions($configOptionsBuilder);
        $options = $configOptionsBuilder->getOptions();

        try {
            $options->validateConfig($configuration);

            $this->output->writeln(
                sprintf(' - %s: <info>valid configuration</info>', $toolName),
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            return true;
        } catch (InvalidConfigException $exception) {
            $this->output->writeln(
                sprintf(' - %s: <error>Invalid configuration (%s)</error>', $toolName, $exception->getMessage()),
                OutputInterface::VERBOSITY_VERBOSE
            );

            return false;
        }
    }
}
