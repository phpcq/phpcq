<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Runner\Repository;

use Phpcq\RepositoryDefinition\Exception\ToolNotFoundException;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Exception\ToolVersionNotFoundException;
use Phpcq\Runner\Repository\RepositoryHasToolVersionTrait;
use Phpcq\Runner\Test\WithConsecutiveTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Repository\RepositoryHasToolVersionTrait
 */
final class RepositoryHasToolVersionTraitTest extends TestCase
{
    use WithConsecutiveTrait;

    public function testCallsGetToolAndReturnsTrueOnMatch(): void
    {
        $createToolVersion = fn (): ToolVersionInterface => $this->createMock(ToolVersionInterface::class);
        $class = new class ($createToolVersion) {
            use RepositoryHasToolVersionTrait;

            private $toolVersionFactory;

            public function __construct(callable $toolVersionFactory)
            {
                $this->toolVersionFactory = $toolVersionFactory;
            }

            public function getToolVersion(string $name, string $versionConstraint): ToolVersionInterface
            {
                if ($name !== 'supertool') {
                    throw new ToolNotFoundException($name);
                }

                return match ($versionConstraint) {
                    '1.0.0.0' => ($this->toolVersionFactory)(),
                    default => throw new ToolVersionNotFoundException($name, $versionConstraint),
                };
            }
        };

        $this->assertTrue($class->hasToolVersion('supertool', '1.0.0.0'));
        $this->assertFalse($class->hasToolVersion('supertool', '1.0.0.1'));
    }
}
