<?php

declare(strict_types=1);

namespace Phpcq\GnuPG;

use PharIo\FileSystem\Directory;
use PharIo\GnuPG\Factory as PharIoGnuPGFactory;

final class GnuPGFactory
{
    /** @var string */
    private $homeDirectory;

    public function __construct(string $homeDirectory)
    {
        $this->homeDirectory = $homeDirectory;
    }

    public function create() : GnuPGInterface
    {
        $instance = (new PharIoGnuPGFactory())->createGnuPG(new Directory($this->homeDirectory));

        return new GnuPGDecorator($instance);
    }
}
