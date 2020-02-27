<?php

namespace Phpcq\Repository;

/**
 * Describes a bootstrap.
 */
interface BootstrapInterface
{
    public function getPluginVersion(): string;

    public function getCode(): string;
}
