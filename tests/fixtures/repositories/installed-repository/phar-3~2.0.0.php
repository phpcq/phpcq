<?php

use Phpcq\PluginApi\Version10\PluginInterface;

return new class implements PluginInterface {
    public function getName(): string
    {
        return 'phar-3';
    }
};
