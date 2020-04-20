<?php

namespace Phpcq\Repository;

use Phpcq\Exception\RuntimeException;

/**
 * Remote bootstrap loader.
 */
class InlineBootstrap implements BootstrapInterface
{
    /**
     * @var string
     */
    private $code;

    public function __construct(string $version, string $code)
    {
        if ($version !== '1.0.0') {
            throw new RuntimeException('Invalid version string: ' . $version);
        }

        $this->code = $code;
    }

    public function getPluginVersion(): string
    {
        return '1.0.0';
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
