<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Plugin\Config\PhpcqConfigurationOptionsBuilder;
use Phpcq\Plugin\PluginRegistry;
use Phpcq\PluginApi\Version10\ConfigurationPluginInterface;
use Phpcq\PluginApi\Version10\InvalidConfigException;
use Symfony\Component\Console\Output\OutputInterface;

use function array_key_exists;
use function array_keys;
use function md5;
use function serialize;
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

        $valid = true;
        foreach ($this->config['chains'] as $chainName => $chainTools) {
            $this->output->writeln('Validate chain "' . $chainName . '":', OutputInterface::VERBOSITY_VERY_VERBOSE);

            foreach (array_keys($chainTools) as $toolName) {
                if (!$this->validatePlugin($plugins, $toolName, $chainName)) {
                    $valid = false;
                }
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
     * @param string|null    $chain    Chain
     *
     * @return bool
     */
    protected function validatePlugin(PluginRegistry $plugins, string $toolName, ?string $chain = null): bool
    {
        static $cache = [];

        $plugin = $plugins->getPluginByName($toolName);
        $name   = $plugin->getName();

        if (! $plugin instanceof ConfigurationPluginInterface) {
            return true;
        }

        $configOptionsBuilder = new PhpcqConfigurationOptionsBuilder();
        $configuration        = $chain
            ? $this->config['chains'][$chain][$name]
            : null;

        if (null === $configuration) {
            $configuration = $this->config['tools-config'][$name] ?? [];
        }

        $hash = md5(serialize($configuration));
        if (isset($cache[$toolName]) && array_key_exists($hash, $cache[$toolName])) {
            $this->output->writeln(
                sprintf(' - %s: <info>configuration already validated</info>', $toolName),
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            return $cache[$toolName][$hash];
        }

        $plugin->describeOptions($configOptionsBuilder);
        $options = $configOptionsBuilder->getOptions();

        try {
            $options->validateConfig($configuration);

            $this->output->writeln(
                sprintf(' - %s: <info>valid configuration</info>', $toolName),
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            return $cache[$toolName][$hash] = true;
        } catch (InvalidConfigException $exception) {
            $this->output->writeln(
                sprintf(' - %s: <error>Invalid configuration (%s)</error>', $toolName, $exception->getMessage()),
                OutputInterface::VERBOSITY_VERBOSE
            );

            return $cache[$toolName][$hash] = false;
        }
    }
}
