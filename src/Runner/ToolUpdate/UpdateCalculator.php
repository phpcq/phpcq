<?php

declare(strict_types=1);

namespace Phpcq\Runner\ToolUpdate;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersionInterface;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Repository\Repository;
use Phpcq\Runner\Repository\RepositoryInterface;
use Phpcq\Runner\Repository\RepositoryPool;

/**
 * @psalm-import-type TTool from \Phpcq\ConfigLoader
 *
 * @psalm-type TUpdateTask = array{
 *    type: 'install'|'keep'|'remove'|'upgrade',
 *    message: string,
 *    tool: ToolInformationInterface,
 *    old?: ToolInformationInterface,
 *    signed?: boolean,
 * }
 */
final class UpdateCalculator
{
    /**
     * @var InstalledRepository
     */
    private $installed;

    /**
     * @var RepositoryPool
     */
    private $pool;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var VersionParser
     */
    private $versionParser;

    public function __construct(InstalledRepository $installed, RepositoryPool $pool, OutputInterface $output)
    {
        $this->installed     = $installed;
        $this->pool          = $pool;
        $this->output        = $output;
        $this->versionParser = new VersionParser();
    }

    /**
     * @param bool $forceReinstall Intended to use if no lock file exists. Php file plugin required for all tools.
     *
     * @psalm-param array<string,TTool> $plugins
     * @psalm-return list<TUpdateTask>
     */
    public function calculate(array $plugins, bool $forceReinstall = false): array
    {
        $desired = $this->calculateDesiredPlugins($plugins);

        return $this->calculateTasksToExecute($desired, $plugins, $forceReinstall);
    }

    /**
     * @psalm-param array<string,TTool> $plugins
     */
    private function calculateDesiredPlugins(array $plugins): RepositoryInterface
    {
        $desired = new Repository();
        foreach ($plugins as $pluginName => $plugin) {
            $desired->addPluginVersion($pluginVersion = $this->pool->getPluginVersion($pluginName, $plugin['version']));
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
     * @psalm-param array<string,TTool> $tools
     *
     * @return array[]
     * @psalm-return list<TUpdateTask>
     */
    public function calculateTasksToExecute(
        RepositoryInterface $desired,
        array $plugins,
        bool $forceReinstall = false
    ): array {
        // Determine diff to current installation.
        $tasks = [];
        /** @var PhpFilePluginVersion $pluginVersion */
        foreach ($desired->iteratePluginVersions() as $pluginVersion) {
            $name = $pluginVersion->getName();

            // Not installed yet => install.
            if (!$this->installed->hasPlugin($name)) {
                $message = 'Will install ' . $name . ' in version ' . $pluginVersion->getVersion();
                $this->output->writeln($message, OutputInterface::VERBOSITY_VERY_VERBOSE);
                $tasks[] = [
                    'type'    => 'install',
                    'version' => $pluginVersion,
                    'message' => $message,
                    'signed'  => $plugins[$pluginVersion->getName()]['signed']
                ];
                continue;
            }
            // Installed in another version => upgrade.
            if ($forceReinstall || $this->isUpgradeRequired($pluginVersion)) {
                $installed = $this->installed->getPlugin($name);
                $message   = $this->getTaskMessage($installed->getPluginVersion(), $pluginVersion);
                $this->output->writeln($message, OutputInterface::VERBOSITY_VERY_VERBOSE);
                $tasks[] = [
                    'type'    => 'upgrade',
                    'version' => $pluginVersion,
                    'old'     => $installed->getPluginVersion(),
                    'message' => $message,
                    'signed'  => $plugins[$pluginVersion->getName()]['signed']
                ];
                continue;
            }
            // Keep the tool otherwise.
            $tasks[] = [
                'type'    => 'keep',
                'plugin'  => $this->installed->getPlugin($name),
                'version' => $this->installed->getPlugin($name)->getPluginVersion(),
                'message' => 'Will keep ' . $name . ' in version ' . $pluginVersion->getVersion(),
            ];
        }
        // Determine uninstalls now.
        /** @var InstalledPlugin $installedPlugin */
        foreach ($this->installed->iteratePlugins() as $installedPlugin) {
            $name = $installedPlugin->getName();
            if (!$desired->hasToolVersion($name, '*')) {
                $message = 'Will remove ' . $name . ' version ' . $installedPlugin->getPluginVersion()->getVersion();
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

    private function getTaskMessage(
        PluginVersionInterface $oldVersion,
        PluginVersionInterface $plugin
    ): string {

        switch (version_compare($oldVersion->getVersion(), $plugin->getVersion())) {
            case 0:
                return 'Will reinstall ' . $plugin->getName() . ' in version ' . $plugin->getVersion();

            case 1:
                return 'Will downgrade ' . $plugin->getName() . ' from version ' . $oldVersion->getVersion()
                    . ' to version ' . $plugin->getVersion();

            case -1:
            default:
                return 'Will upgrade ' . $plugin->getName() . ' from version ' . $oldVersion->getVersion()
                    . ' to version ' . $plugin->getVersion();
        }
    }

    private function isUpgradeRequired(PhpFilePluginVersionInterface $desired): bool
    {
        if (! $this->installed->hasPlugin($desired->getName())) {
            return true;
        }

        $installed = $this->installed->getPlugin($desired->getName());
        $constraints = $this->versionParser->parseConstraints($desired->getVersion());

        if ($constraints->matches(new Constraint('=', $installed->getPluginVersion()->getVersion()))) {
            return true;
        }

        return $this->hasHashChanged($desired, $installed->getPluginVersion());
    }

    private function hasHashChanged(PhpFilePluginVersionInterface $plugin, PhpFilePluginVersionInterface $installed): bool
    {
        // No hash given. We can't verify changes, force reinstall
        if (null === ($bootstrapHash = $plugin->getHash())) {
            return true;
        }

        // No hash given for installed tool but new version has a hash, forche reinstall
        if (null === ($installedHash = $installed->getHash())) {
            return true;
        }

        return !$bootstrapHash->equals($installedHash);
    }
}
