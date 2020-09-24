<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test;

use Symfony\Component\Filesystem\Filesystem;

use function sys_get_temp_dir;

trait TemporaryFileProducingTestTrait
{
    private static $tempdir;

    public static function setUpBeforeClass(): void
    {
        self::$tempdir = sys_get_temp_dir() . '/' . uniqid('phpcq-test-');
        $filesystem = new Filesystem();
        $filesystem->mkdir(self::$tempdir);
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove(self::$tempdir);
        parent::tearDownAfterClass();
    }
}
