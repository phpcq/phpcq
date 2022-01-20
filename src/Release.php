<?php

declare(strict_types=1);

namespace Phpcq\Runner;

use DateTimeImmutable;
use Phpcq\Runner\Exception\InvalidArgumentException;

use function preg_match;
use function preg_quote;

/** @psalm-immutable */
final class Release
{
    public const DATE_FORMAT = 'Y-m-d-H-i-s-T';

    /** @var string */
    private $version;

    /** @var string */
    private $gitVersion;

    /** @var DateTimeImmutable */
    private $buildDate;

    public static function fromString(string $release, ?string $prefix = null): self
    {
        $prefix  = $prefix ? preg_quote($prefix, '#') : '';
        $pattern = sprintf('#%s(.+)-(\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}-[^-]+)-(.+)#', $prefix);

        if (! preg_match($pattern, $release, $matches)) {
            throw new InvalidArgumentException(sprintf('Could not parse release information from "%s"', $release));
        }

        $buildDate = DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $matches[2]);
        if ($buildDate === false) {
            throw new InvalidArgumentException(sprintf('Could not parse build date "%s"', $matches[2]));
        }

        return new self($matches[1], $matches[3], $buildDate);
    }

    public function __construct(string $version, string $gitVersion, DateTimeImmutable $buildDate)
    {
        $this->version    = $version;
        $this->buildDate  = $buildDate;
        $this->gitVersion = $gitVersion;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getGitVersion(): string
    {
        return $this->gitVersion;
    }

    public function getBuildDate(): DateTimeImmutable
    {
        return $this->buildDate;
    }

    public function equals(Release $other): bool
    {
        if ($this->version !== $other->version) {
            return false;
        }

        if ($this->gitVersion !== $other->gitVersion) {
            return false;
        }

        return $this->buildDate == $other->buildDate;
    }
}
