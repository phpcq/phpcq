<?php

declare(strict_types=1);

namespace Phpcq\Output;

interface OutputInterface
{
    public const VERBOSITY_QUIET = 16;
    public const VERBOSITY_NORMAL = 32;
    public const VERBOSITY_VERBOSE = 64;
    public const VERBOSITY_VERY_VERBOSE = 128;
    public const VERBOSITY_DEBUG = 256;

    public const CHANNEL_STDOUT = 1;
    public const CHANNEL_STRERR = 2;

    public function write(string $message, int $verbosity = self::VERBOSITY_NORMAL, int $channel = self::CHANNEL_STDOUT) : void;

    public function writeln(string $message, int $verbosity = self::VERBOSITY_NORMAL, int $channel = self::CHANNEL_STDOUT) : void;
}
