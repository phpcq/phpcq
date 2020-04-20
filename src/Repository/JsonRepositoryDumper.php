<?php

declare(strict_types=1);

namespace Phpcq\Repository;

class JsonRepositoryDumper
{
    /**
     * @var string
     */
    private $destinationPath;

    /**
     * @param string $destinationPath Output path for .phar files and bootstraps.
     */
    public function __construct(string $destinationPath)
    {
        $this->destinationPath = $destinationPath;
    }

    public function dump(RepositoryInterface $repository, string $filename): void
    {
        $data = ['phars' => []];
        foreach ($repository as $tool) {
            /** @var ToolInformationInterface $tool */
            $name = $tool->getName();
            if (!isset($data['phars'][$name])) {
                $data['phars'][$name][] = [
                    'version'       => $tool->getVersion(),
                    'phar-url'      => $tool->getPharUrl(),
                    'bootstrap'     => $this->dumpBootstrap($tool),
                    'requirements'  => $tool->getPlatformRequirements(),
                    'signature'     => $tool->getSignatureUrl(),
                ];
            }
        }

        file_put_contents($this->destinationPath . '/' . $filename, json_encode($data));
    }

    private function dumpBootstrap(ToolInformationInterface $tool): array
    {
        $bootFile = sprintf('%1$s~%2$s.php', $tool->getName(), $tool->getVersion());
        file_put_contents($this->destinationPath . '/' . $bootFile, $tool->getBootstrap()->getCode());

        return [
            'plugin-version' => $tool->getBootstrap()->getPluginVersion(),
            'type' => 'file',
            'url'  => $bootFile
        ];
    }
}
