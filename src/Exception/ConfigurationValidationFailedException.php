<?php

declare(strict_types=1);

namespace Phpcq\Exception;

use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use Throwable;

class ConfigurationValidationFailedException extends InvalidConfigurationException
{
    /** @var InvalidConfigurationException|null $rootError */
    private $rootError;

    /** @psalm-var list<string> */
    private $path;

    protected function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function fromRootError(
        array $path,
        InvalidConfigurationException $rootException,
        Throwable $previous = null
    ): self {
        $exception = new self(
            'Configuration validation failed at path "' . implode('.', $path) . '" with message:'
            . $rootException->getMessage(),
            null,
            $previous ?: $rootException
        );
        $exception->rootError = $rootException;
        $exception->path = $path;

        return $exception;
    }

    public static function fromPreviousError(array $outerPath, ConfigurationValidationFailedException $previous): self
    {
        $exception = new self(
            'Configuration validation failed at path "' . implode('.', $outerPath) . '" with message:'
            . $previous->getRootError()->getMessage(),
            null,
            $previous
        );
        $exception->rootError = $previous->getRootError();
        $exception->path = array_merge($outerPath, $previous->getPath());

        return $exception;
    }

    public function getRootError(): InvalidConfigurationException
    {
        return $this->rootError ?: $this;
    }

    public function getPath(): array
    {
        return $this->path;
    }
}
