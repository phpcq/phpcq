<?php

declare(strict_types=1);

namespace Phpcq\GnuPG;

use PharIo\FileSystem\Directory;
use PharIo\GnuPG\Factory as PharIoGnuPGFactory;

final class GnuPGFactory
{
    /** @var string */
    private $homeDirectory;

    /**
     * @var PharIoGnuPGFactory
     */
    private $factory;

    public function __construct(string $homeDirectory, PharIoGnuPGFactory $factory = null)
    {
        $this->homeDirectory = $homeDirectory;
        $this->factory       = $factory ?: new PharIoGnuPGFactory();
    }

    public function create() : GnuPGInterface
    {
        $instance = $this->factory->createGnuPG(new Directory($this->homeDirectory));

        return new GnuPGDecorator($instance);
    }
}
