<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\RepositoryDefinition\AbstractHash;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Repository\Repository;
use Phpcq\Runner\Repository\RepositoryInterface;
use Phpcq\Runner\Resolver\ResolverInterface;

use function sprintf;
use function version_compare;

/**
 * @psalm-import-type TPlugin from \Phpcq\Runner\Config\PhpcqConfiguration
 *
 * @psalm-type TInstallToolTask = array{
 *    type: 'install',
 *    tool: \Phpcq\RepositoryDefinition\Tool\ToolVersionInterface,
 *    message: string,
 *    old: \Phpcq\RepositoryDefinition\Tool\ToolVersionInterface,
 *    signed: bool,
 * }
 *
 * @psalm-type TUpgradeToolTask = array{
 *    type: 'upgrade',
 *    tool: \Phpcq\RepositoryDefinition\Tool\ToolVersionInterface,
 *    message: string,
 *    old: \Phpcq\RepositoryDefinition\Tool\ToolVersionInterface,
 *    signed: bool,
 * }
 *
 * @psalm-type TKeepToolTask = array{
 *    type: 'keep',
 *    tool: \Phpcq\RepositoryDefinition\Tool\ToolVersionInterface,
 *    installed: \Phpcq\RepositoryDefinition\Tool\ToolVersionInterface,
 *    message: string,
 * }
 *
 * @psalm-type TRemoveToolTask = array{
 *    type: 'remove',
 *    tool: \Phpcq\RepositoryDefinition\Tool\ToolVersionInterface,
 *    message: string,
 * }
 *
 * @psalm-type TToolTask = TInstallToolTask|TUpgradeToolTask|TKeepToolTask|TRemoveToolTask
 *
 * @psalm-type TInstallPluginTask = array{
 *    type: 'install',
 *    version: \Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface,
 *    message: string,
 *    signed: boolean,
 *    tasks: list<TToolTask>
 * }
 *
 * @psalm-type TUpgradePluginTask = array{
 *    type: 'upgrade',
 *    version: \Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface,
 *    old: \Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface,
 *    message: string,
 *    signed: boolean,
 *    tasks: list<TToolTask>
 * }
 *
 * @psalm-type TKeepPluginTask = array{
 *    type: 'keep',
 *    plugin: \Phpcq\Runner\Repository\InstalledPlugin,
 *    version: \Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface,
 *    message: string,
 *    tasks: list<TToolTask>
 * }
 *
 * @psalm-type TRemovePluginTask = array{
 *    type: 'remove',
 *    version: \Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface,
 *    plugin: \Phpcq\Runner\Repository\InstalledPlugin,
 *    message: string,
 * }
 *
 * @psalm-type TPluginTask = TInstallPluginTask|TUpgradePluginTask|TKeepPluginTask|TRemovePluginTask
 */
final class UpdateCalculator
{
    /**
     * @var InstalledRepository
     */
    private $installed;

    /**
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var VersionParser
     */
    private $versionParser;

    public function __construct(InstalledRepository $installed, ResolverInterface $resolver, OutputInterface $output)
    {
        $this->installed     = $installed;
        $this->output        = $output;
        $this->resolver      = $resolver;
        $this->versionParser = new VersionParser();
    }

    /**
     * @param bool $forceReinstall Intended to use if no lock file exists. Php file plugin required for all tools.
     *
     * @psalm-param array<string,TPlugin> $plugins
     * @psalm-return list<TPluginTask>
     */
    public function calculate(array $plugins, bool $forceReinstall = false): array
    {
        return $this->calculateTasksToExecute($this->calculateDesiredPlugins($plugins), $plugins, $forceReinstall);
    }

    /**
     * @psalm-param array<string,TPlugin> $plugins
     */
    private function calculateDesiredPlugins(array $plugins): RepositoryInterface
    {
        $desired = new Repository();
        foreach ($plugins as $pluginName => $plugin) {
            $pluginVersion = $this->resolver->resolvePluginVersion($pluginName, $plugin['version']);
            $desired->addPluginVersion($pluginVersion);
            $this->output->writeln(
                'Want ' . $pluginVersion->getName() . ' in version ' . $pluginVersion->getVersion(),
                OutputInterface::VERBOSITY_DEBUG
            );
        }

        return $desired;
    }

    /**
     * @param bool $forceReinstall Intended to use if no lock file exists. Php file plugin required for all tools.
     *
     * @psalm-param array<string,TPlugin> $plugins
     *
     * @return array[]
     * @psalm-return list<TPluginTask>
     */
    protected function calculateTasksToExecute(
        RepositoryInterface $desired,
        array $plugins,
        bool $forceReinstall = false
    ): array {
        // Determine diff to current installation.
        $tasks = [];
        foreach ($desired->iteratePluginVersions() as $pluginVersion) {
            $name = $pluginVersion->getName();

            // Not installed yet => install.
            if (!$this->installed->hasPlugin($name)) {
                $message = 'Will install plugin ' . $name . ' in version ' . $pluginVersion->getVersion();
                $this->output->writeln($message, OutputInterface::VERBOSITY_VERY_VERBOSE);
                $tasks[] = [
                    'type'    => 'install',
                    'version' => $pluginVersion,
                    'message' => $message,
                    'signed'  => $plugins[$pluginVersion->getName()]['signed'] ?? true,
                    'tasks'   => $this->calculateToolTasks($pluginVersion, $plugins, $forceReinstall)
                ];
                continue;
            }
            // Installed in another version => upgrade.
            $installed = $this->installed->getPlugin($name);
            if ($forceReinstall || $this->isPluginUpgradeRequired($pluginVersion)) {
                $message   = $this->getPluginTaskMessage($installed->getPluginVersion(), $pluginVersion);
                $this->output->writeln($message, OutputInterface::VERBOSITY_VERY_VERBOSE);
                $tasks[] = [
                    'type'    => 'upgrade',
                    'version' => $pluginVersion,
                    'old'     => $installed->getPluginVersion(),
                    'message' => $message,
                    'signed'  => $plugins[$pluginVersion->getName()]['signed'] ?? true,
                    'tasks'   => $this->calculateToolTasks($pluginVersion, $plugins, $forceReinstall)
                ];
                continue;
            }
            // Keep the tool otherwise.
            $tasks[] = [
                'type'    => 'keep',
                'plugin'  => $installed,
                'version' => $pluginVersion,
                'message' => 'Will keep plugin ' . $name . ' in version ' . $pluginVersion->getVersion(),
                'tasks'   => $this->calculateToolTasks($pluginVersion, $plugins, $forceReinstall)
            ];
        }
        // Determine uninstalls now.
        foreach ($this->installed->iteratePlugins() as $installedPlugin) {
            $name = $installedPlugin->getName();
            if (!$desired->hasPluginVersion($name, '*')) {
                $message = 'Will remove plugin ' . $name . ' version '
                    . $installedPlugin->getPluginVersion()->getVersion();
                $this->output->writeln($message, OutputInterface::VERBOSITY_VERY_VERBOSE);
                $tasks[] = [
                    'type'    => 'remove',
                    'plugin'  => $installedPlugin,
                    'version' => $installedPlugin->getPluginVersion(),
                    'message' => $message,
                ];
            }
        }

        return $tasks;
    }

    private function getPluginTaskMessage(
        PluginVersionInterface $oldVersion,
        PluginVersionInterface $plugin
    ): string {
        /** @psalm-suppress RedundantCondition - We experience different behaviour using or not using default branch */
        switch (version_compare($oldVersion->getVersion(), $plugin->getVersion())) {
            case 1:
                return 'Will downgrade plugin ' . $plugin->getName() . ' from version ' . $oldVersion->getVersion()
                    . ' to version ' . $plugin->getVersion();

            case -1:
                return 'Will upgrade plugin ' . $plugin->getName() . ' from version ' . $oldVersion->getVersion()
                    . ' to version ' . $plugin->getVersion();

            case 0:
            default:
        }
        return 'Will reinstall plugin ' . $plugin->getName() . ' in version ' . $plugin->getVersion();
    }

    private function isPluginUpgradeRequired(PluginVersionInterface $desired): bool
    {
        if (! $this->installed->hasPlugin($desired->getName())) {
            return true;
        }

        $installed = $this->installed->getPlugin($desired->getName());
        if (Semver::satisfies($installed->getPluginVersion()->getVersion(), $desired->getVersion())) {
            // FIXME: Remove usage of hasHashChanged
            /** @psalm-suppress DeprecatedMethod */
            return $this->hasHashChanged($desired->getHash(), $installed->getPluginVersion()->getHash());
        }

        return true;
    }

    /** @deprecated Remove when we have versioned plugins */
    private function hasHashChanged(?AbstractHash $desired, ?AbstractHash $installed): bool
    {
        // No hash given. We can't verify changes, force reinstall
        if (null === $desired) {
            return true;
        }

        // No hash given for installed tool but new version has a hash, forche reinstall
        if (null === $installed) {
            return true;
        }

        return !$desired->equals($installed);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @psalm-param array<string,TPlugin> $plugins
     *
     * @psalm-return list<TToolTask>
     */
    private function calculateToolTasks(PluginVersionInterface $desired, array $plugins, bool $forceReinstall): array
    {
        $config     = $plugins[$desired->getName()] ?? [];
        $pluginName = $desired->getName();
        $plugin     = $this->installed->hasPlugin($pluginName) ? $this->installed->getPlugin($pluginName) : null;
        $tasks      = [];

        foreach ($desired->getRequirements()->getToolRequirements() as $toolRequirement) {
            // FIXME: Check if configured requirement is within the tool requirement
            $requirementName = $toolRequirement->getName();
            $constraint = $config['requirements'][$requirementName]['version']
                ?? $toolRequirement->getConstraint();
            $tool       = $this->resolver->resolveToolVersion($pluginName, $requirementName, $constraint);
            $toolName   = $tool->getName();

            if (!$plugin || !$plugin->hasTool($requirementName)) {
                $message = sprintf('Will install tool %s in version %s', $toolName, $tool->getVersion());
                $this->output->writeln($message, OutputInterface::VERBOSITY_VERY_VERBOSE);

                $tasks[] = [
                    'type'    => 'install',
                    'tool'    => $tool,
                    'message' => $message,
                    'signed'  => $config['requirements'][$toolName]['signed'] ?? true,
                ];
                continue;
            }
            // Installed in another version => upgrade.
            $installed = $plugin->getTool($toolName);
            if ($forceReinstall || $this->isToolUpgradeRequired($plugin, $tool)) {
                $message   = $this->getToolTaskMessage($installed, $tool);
                $this->output->writeln($message, OutputInterface::VERBOSITY_VERY_VERBOSE);

                $tasks[] = [
                    'type'    => 'upgrade',
                    'tool'    => $tool,
                    'message' => $message,
                    'old'     => $installed,
                    'signed'  => $config['requirements'][$toolName]['signed'] ?? true,
                ];
                continue;
            }
            // Keep the tool otherwise.
            $tasks[] = [
                'type'      => 'keep',
                'tool'      => $tool,
                'installed' => $installed,
                'message'   => 'Will keep tool ' . $installed->getName() . ' in version ' . $installed->getVersion(),
            ];
        }

        if ($plugin) {
            foreach ($plugin->iterateTools() as $tool) {
                if (! $desired->getRequirements()->getToolRequirements()->has($tool->getName())) {
                    $message = 'Will remove tool ' . $tool->getName() . ' version ' . $tool->getVersion();
                    $this->output->writeln($message, OutputInterface::VERBOSITY_VERY_VERBOSE);
                    $tasks[] = [
                        'type'    => 'remove',
                        'tool'    => $tool,
                        'message' => $message,
                    ];
                }
            }
        }

        return $tasks;
    }

    private function isToolUpgradeRequired(InstalledPlugin $plugin, ToolVersionInterface $desired): bool
    {
        $installed = $plugin->getTool($desired->getName());
        if (Semver::satisfies($installed->getVersion(), $desired->getVersion())) {
            // FIXME: Remove usage of hasHashChanged
            /** @psalm-suppress DeprecatedMethod */
            return $desired->getHash() && $this->hasHashChanged($desired->getHash(), $installed->getHash());
        }

        return true;
    }

    private function getToolTaskMessage(
        ToolVersionInterface $oldVersion,
        ToolVersionInterface $tool
    ): string {
        /** @psalm-suppress RedundantCondition - We experience different behaviour using or not using default branch */
        switch (version_compare($oldVersion->getVersion(), $tool->getVersion())) {
            case 1:
                return 'Will downgrade tool ' . $tool->getName() . ' from version ' . $oldVersion->getVersion()
                    . ' to version ' . $tool->getVersion();
            case -1:
                return 'Will upgrade tool ' . $tool->getName() . ' from version ' . $oldVersion->getVersion()
                    . ' to version ' . $tool->getVersion();
            case 0:
            default:
        }
        return 'Will reinstall tool ' . $tool->getName() . ' in version ' . $tool->getVersion();
    }
}
