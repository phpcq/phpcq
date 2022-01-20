<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test;

use DateTimeImmutable;
use Generator;
use Phpcq\Runner\Release;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Release */
final class ReleaseTest extends TestCase
{
    public function testInstantiationFromString(): void
    {
        $version      = '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc';
        $release      = Release::fromString($version);
        $expectedDate = DateTimeImmutable::createFromFormat(Release::DATE_FORMAT, '2022-01-19-16-01-15-UTC');

        self::assertEquals('0.0.0.1-dev', $release->getVersion());
        self::assertEquals('a4873bc', $release->getGitVersion());
        self::assertEquals($expectedDate, $release->getBuildDate());
    }

    public function testInstantiationFromStringWithPrefix(): void
    {
        $version      = 'phpcq 0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc';
        $release      = Release::fromString($version, 'phpcq ');
        $expectedDate = DateTimeImmutable::createFromFormat(Release::DATE_FORMAT, '2022-01-19-16-01-15-UTC');

        self::assertEquals('0.0.0.1-dev', $release->getVersion());
        self::assertEquals('a4873bc', $release->getGitVersion());
        self::assertEquals($expectedDate, $release->getBuildDate());
    }

    public function provideEqual(): Generator
    {
        yield 'Equal release' => [
            'expected' => true,
            'first'    => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
            'second'   => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
        ];

        yield 'Different version' => [
            'expected' => false,
            'first'    => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
            'second'   => '0.0.0.2-dev-2022-01-19-16-01-15-UTC-a4873bc',
        ];

        yield 'Different build date' => [
            'expected' => false,
            'first'    => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
            'second'   => '0.0.0.1-dev-2021-01-19-16-01-15-UTC-a4873bc',
        ];

        yield 'Different git version' => [
            'expected' => false,
            'first'    => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873bc',
            'second'   => '0.0.0.1-dev-2022-01-19-16-01-15-UTC-a4873ff',
        ];
    }

    /** @dataProvider provideEqual */
    public function testEquals(bool $expected, string $first, string $second): void
    {
        $fromRelease = Release::fromString($first);
        $toRelease   = Release::fromString($second);

        self::assertEquals($expected, $fromRelease->equals($toRelease));
    }
}
