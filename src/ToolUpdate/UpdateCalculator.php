<?php

declare(strict_types=1);

namespace Phpcq\ToolUpdate;

use Phpcq\PluginApi\Version10\OutputInterface;
use Phpcq\Repository\Repository;
use Phpcq\Repository\RepositoryInterface;
use Phpcq\Repository\RepositoryPool;
use Phpcq\Repository\ToolInformationInterface;

final class UpdateCalculator
{
    /**
     * @var RepositoryInterface
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

    public function __construct(RepositoryInterface $installed, RepositoryPool $pool, OutputInterface $output)
    {
        $this->installed = $installed;
        $this->pool      = $pool;
        $this->output    = $output;
    }

    /**
     * @param bool $forceReinstall Intended to use if no lock file exists. Remote tool information required for all
     *                             tools.
     */
    public function calculate(array $tools, bool $forceReinstall = false): array
    {
        $desired = $this->calculateDesiredTools($tools);

        return $this->calculateTasksToExecute($desired, $tools, $forceReinstall);
    }

    private function calculateDesiredTools(array $tools): RepositoryInterface
    {
        $desired = new Repository();
        foreach ($tools as $toolName => $tool) {
            $desired->addVersion($toolVersion = $this->pool->getTool($toolName, $tool['version']));
            $this->output->writeln(
                'Want ' . $toolVersion->getName() . ' in version ' . $toolVersion->getVersion(),
                OutputInterface::VERBOSITY_DEBUG
            );
        }

        return $desired;
    }

    /**
     * @param bool $forceReinstall Intended to use if no lock file exists. Remote tool information required for all
     *                             tools.
     *
     * @return (ToolInformationInterface|mixed|string)[][]
     *
     * @psalm-return list<array{type: string, tool: ToolInformationInterface, old?: ToolInformationInterface,
     * message: string, signed?: mixed}>
     */
    public function calculateTasksToExecute(
        RepositoryInterface $desired,
        array $tools,
        bool $forceReinstall = false
    ): array {
        // Determine diff to current installation.
        $tasks = [];
        foreach ($desired as $tool) {
            /** @var ToolInformationInterface $tool */
            $name = $tool->getName();
            // Not installed yet => install.
            if (!$this->installed->hasTool($name, '*')) {
                $message = 'Will install ' . $name . ' in version ' . $tool->getVersion();
                $this->output->writeln($message, OutputInterface::VERBOSITY_VERY_VERBOSE);
                $tasks[] = [
                    'type' => 'install',
                    'tool' => $tool,
                    'message' => $message,
                    'signed' => $tools[$tool->getName()]['signed']
                ];
                continue;
            }
            // Installed in another version => upgrade.
            if ($forceReinstall || !$this->installed->hasTool($name, $tool->getVersion())) {
                $oldVersion = $this->installed->getTool($name, '*');

                $message = 'Will ' . $this->describeUpgradeDirection($oldVersion, $tool) . ' ' . $name
                    . ' from version ' . $oldVersion->getVersion() . ' to version ' . $tool->getVersion();
                
                $this->output->writeln($message, OutputInterface::VERBOSITY_VERY_VERBOSE);
                $tasks[] = [
                    'type' => 'upgrade',
                    'tool' => $tool,
                    'old'  => $this->installed->getTool($name, '*'),
                    'message' => $message,
                    'signed' => $tools[$tool->getName()]['signed']
                ];
                continue;
            }
            // Keep the tool otherwise.
            $tasks[] = [
                'type' => 'keep',
                'tool' => $this->installed->getTool($name, $tool->getVersion()),
                'message' => 'Will keep ' . $name . ' in version ' . $tool->getVersion(),
            ];
        }
        // Determine uninstalls now.
        foreach ($this->installed as $tool) {
            /** @var ToolInformationInterface $tool */
            $name = $tool->getName();
            if (!$desired->hasTool($name, '*')) {
                $message = 'Will remove ' . $name . ' version ' . $tool->getVersion();
                $this->output->writeln($message, OutputInterface::VERBOSITY_VERY_VERBOSE);
                $tasks[] = [
                    'type' => 'remove',
                    'tool' => $tool,
                    'message' => $message,
                ];
            }
        }

        return $tasks;
    }

    private function describeUpgradeDirection(
        ToolInformationInterface $oldVersion,
        ToolInformationInterface $tool
    ): string {
        switch (version_compare($oldVersion->getVersion(), $tool->getVersion())) {
            case 0:
                return 'reinstall';

            case 1:
                return 'downgrade';

            case -1:
            default:
                return 'upgrade';
        }
    }
}
