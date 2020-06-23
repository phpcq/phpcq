<?php

declare(strict_types=1);

namespace Phpcq\Exception;

use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use Throwable;

use function array_merge;

class ConfigurationValidationFailedException extends InvalidConfigurationException
{
    /** @var InvalidConfigurationException|null $rootError */
    private $rootError;

    /** @psalm-var list<string> */
    private $path;

    /** @psalm-param list<string> $path */
    protected function __construct(
        array $path,
        string $message = '',
        int $code = 0,
        Throwable $previous = null,
        ?InvalidConfigurationException $rootError = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->path      = $path;
        $this->rootError = $rootError ?: $this;
    }

    /** @psalm-param list<string> $path */
    public static function fromRootError(
        array $path,
        InvalidConfigurationException $rootException,
        Throwable $previous = null
    ): self {
        return new self(
            $path,
            'Configuration validation failed at path "' . implode('.', $path) . '" with message:'
            . $rootException->getMessage(),
            0,
            $previous ?: $rootException,
            $rootException
        );
    }

    /** @psalm-param list<string> $outerPath */
    public static function fromPreviousError(array $outerPath, ConfigurationValidationFailedException $previous): self
    {
        return new self(
            array_merge($outerPath, $previous->getPath()),
            'Configuration validation failed at path "' . implode('.', $outerPath) . '" with message:'
            . $previous->getRootError()->getMessage(),
            0,
            $previous,
            $previous->getRootError()
        );
    }

    public function getRootError(): InvalidConfigurationException
    {
        return $this->rootError ?: $this;
    }

    /** @psalm-return list<string> */
    public function getPath(): array
    {
        return $this->path;
    }
}
