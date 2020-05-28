<?php

declare(strict_types=1);

namespace Phpcq\Exception;

use Phpcq\PluginApi\Version10\RuntimeException as PluginApiRuntimeException;

class RuntimeException extends PluginApiRuntimeException implements Exception
{
}
