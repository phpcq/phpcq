<?php

declare(strict_types=1);

namespace Phpcq\Runner\Exception;

use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use Throwable;

use function array_merge;
use function implode;

class ConfigurationValidationErrorException extends InvalidConfigurationException
{
    private ?Throwable $rootError = null;

    /** @param list<string> $path */
    protected function __construct(
        /** @var list<string> */
        private readonly array $path,
        string $message = '',
        int $code = 0,
        Throwable $previous = null,
        ?Throwable $rootError = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->rootError = $rootError ?: $this;
    }

    /** @param list<string> $path */
    public static function withCustomMessage(array $path, string $message, Throwable $previous = null): self
    {
        return new self($path, $message, 0, $previous);
    }

    /** @param list<string> $path */
    public static function fromError(
        array $path,
        Throwable $rootException,
        Throwable $previous = null
    ): self {
        return new self(
            $path,
            'Configuration validation failed at path "' . implode('.', $path) . '": '
            . $rootException->getMessage(),
            0,
            $previous ?: $rootException,
            $rootException
        );
    }

    /** @param list<string> $outerPath */
    public function withOuterPath(array $outerPath): self
    {
        $path = array_merge($outerPath, $this->getPath());
        return new self(
            $path,
            'Configuration validation failed at path "' . implode('.', $path) . '": '
            . $this->getRootError()->getMessage(),
            0,
            $this,
            $this->getRootError()
        );
    }

    public function getRootError(): Throwable
    {
        return $this->rootError ?: $this;
    }

    /** @return list<string> */
    public function getPath(): array
    {
        return $this->path;
    }
}
