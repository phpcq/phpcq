<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Runner\Repository;

use Phpcq\RepositoryDefinition\Exception\RuntimeException;
use Phpcq\Runner\Repository\InstalledRepositoryLoader;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Repository\InstalledRepositoryLoader */
final class InstalledRepositoryLoaderTest extends TestCase
{
    private const FIXTURE = __DIR__ . '/../fixtures/repositories/installed-repository/installed-unsupported-api.json';

    public function testThrowsForUnsupportedApiVersion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Plugin "phar-1" requires plugin API 99.0.0, supported are 1.0.0 - '
        );

        (new InstalledRepositoryLoader())->loadFile(self::FIXTURE);
    }

    public function testSkipsUnsupportedApiVersionWhenNotFailingOnError(): void
    {
        $repository = (new InstalledRepositoryLoader(null, false))->loadFile(self::FIXTURE);

        self::assertFalse($repository->hasPlugin('phar-1'));
    }
}
