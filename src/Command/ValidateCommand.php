<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\ConfigLoader;
use Phpcq\Plugin\Config\PhpcqConfigurationOptionsBuilder;
use Phpcq\Plugin\PluginRegistry;
use Phpcq\PluginApi\Version10\ConfigurationPluginInterface;
use Phpcq\PluginApi\Version10\InvalidConfigException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_keys;
use function assert;
use function is_string;
use function sprintf;

final class ValidateCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('validate')->setDescription('Validate the phpcq installation');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $phpcqPath = $input->getOption('tools');
        assert(is_string($phpcqPath));
        $this->createDirectory($phpcqPath);

        if ($output->isVeryVerbose()) {
            $output->writeln('Using HOME: ' . $phpcqPath);
        }

        $output->writeln('Validate phpcq configuration', OutputInterface::VERBOSITY_VERY_VERBOSE);

        $configFile = $input->getOption('config');
        assert(is_string($configFile));
        $config     = ConfigLoader::load($configFile);

        $plugins = PluginRegistry::buildFromInstalledJson($phpcqPath . '/installed.json');
        $valid   = true;

        $output->writeln('Validate plugins:', OutputInterface::VERBOSITY_VERY_VERBOSE);

        foreach (array_keys($config['tools']) as $toolName) {
            if (!$this->validatePlugin($plugins, $toolName, $config, $output)) {
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
     * @param PluginRegistry         $plugins  The plugin registry.
     * @param string                 $toolName The tool name being validated.
     * @param array<string, mixed[]> $config   The tools configuration.
     * @param OutputInterface        $output   The console output.
     *
     * @return bool
     */
    protected function validatePlugin(PluginRegistry $plugins, string $toolName, array $config, OutputInterface $output): bool
    {
        $plugin = $plugins->getPluginByName($toolName);
        $name   = $plugin->getName();

        if (! $plugin instanceof ConfigurationPluginInterface) {
            return true;
        }

        $configOptionsBuilder = new PhpcqConfigurationOptionsBuilder();
        $configuration        = $config[$name] ?? [];

        $plugin->describeOptions($configOptionsBuilder);
        $options = $configOptionsBuilder->getOptions();

        try {
            $options->validateConfig($configuration);

            $output->writeln(
                sprintf(' - %s: <info>valid configuration</info>', $toolName),
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            return true;
        } catch (InvalidConfigException $exception) {
            $output->writeln(
                sprintf(' - %s: <error>Invalid configuration (%s)</error>', $toolName, $exception->getMessage()),
                OutputInterface::VERBOSITY_VERBOSE
            );

            return false;
        }
    }
}
