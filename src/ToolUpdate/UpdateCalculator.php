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

    public function calculate(array $tools): array
    {
        $desired = $this->calculateDesiredTools($tools);

        return $this->calculateTasksToExecute($desired, $tools);
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
     * @return (ToolInformationInterface|mixed|string)[][]
     *
     * @psalm-return list<array{type: string, tool: ToolInformationInterface, old?: ToolInformationInterface,
     * message: string, signed?: mixed}>
     */
    private function calculateTasksToExecute(RepositoryInterface $desired, array $tools): array
    {
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
            if (!$this->installed->hasTool($name, $tool->getVersion())) {
                $oldVersion = $this->installed->getTool($name, '*');

                $message = 'Will ' . (
                    version_compare($oldVersion->getVersion(), $tool->getVersion(), '<')
                        ? 'upgrade '
                        : 'downgrade '
                    ) . $name . ' from version ' . $oldVersion->getVersion() . ' to version ' . $tool->getVersion();
                
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
}
