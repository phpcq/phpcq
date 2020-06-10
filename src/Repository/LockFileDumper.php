<?php

declare(strict_types=1);

namespace Phpcq\Repository;

class LockFileDumper
{
    /**
     * @var string
     */
    private $destinationPath;

    /**
     * @var int
     */
    private $bootstrapIndex = 0;

    /**
     * @param string $destinationPath Output path for .phar files and bootstraps.
     */
    public function __construct(string $destinationPath)
    {
        $this->destinationPath = $destinationPath;
    }

    public function dump(RepositoryInterface $repository, string $filename): void
    {
        $this->bootstrapIndex = 0;
        $data = [
            'bootstraps' => [],
            'phars' => [],
        ];

        /** @var ToolInformationInterface $tool */
        foreach ($repository as $tool) {
            $name = $tool->getName();

            if (!isset($data['phars'][$name])) {
                $hash = $tool->getHash();
                $data['phars'][$name][] = [
                    'version'      => $tool->getVersion(),
                    'phar-url'     => $tool->getPharUrl(),
                    'bootstrap'    => $this->dumpBootstrap($tool, $data['bootstraps']),
                    'requirements' => $tool->getPlatformRequirements(),
                    'hash'         => $hash
                        ? ['type'  => $hash->getType(), 'value' => $hash->getValue()]
                        : null,
                    'signature'    => $tool->getSignatureUrl(),
                ];
            }
        }

        file_put_contents($this->destinationPath . '/' . $filename, json_encode($data));
    }

    private function dumpBootstrap(ToolInformationInterface $tool, array &$bootstraps): string
    {
        $name = 'bootstrap-' . $this->bootstrapIndex++;
        $bootstraps[$name] = [
            'plugin-version' => $tool->getBootstrap()->getPluginVersion(),
            'type' => 'inline',
            'code' => $tool->getBootstrap()->getCode(),
            'hash' => $this->dumpBootstrapHash($tool),
        ];

        return $name;
    }

    private function dumpBootstrapHash(ToolInformationInterface $tool): ?array
    {
        $hash = $tool->getBootstrap()->getHash();
        if (null === $hash) {
            return null;
        }

        return [
            'type'  => $hash->getType(),
            'value' => $hash->getValue(),
        ];
    }
}
