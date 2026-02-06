<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Runner\Repository;

use Phpcq\RepositoryDefinition\Exception\PluginNotFoundException;
use Phpcq\Runner\Exception\PluginVersionNotFoundException;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Repository\RepositoryHasPluginVersionTrait;
use Phpcq\Runner\Test\WithConsecutiveTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Repository\RepositoryHasPluginVersionTrait
 */
final class RepositoryHasPluginVersionTraitTest extends TestCase
{
    use WithConsecutiveTrait;

    public function testCallsGetToolAndReturnsTrueOnMatch(): void
    {
        $createPluginVersion = fn (): PluginVersionInterface => $this->createMock(PluginVersionInterface::class);
        $class = new class ($createPluginVersion) {
            use RepositoryHasPluginVersionTrait;

            private $pluginVersionFactory;

            public function __construct(callable $pluginVersionFactory)
            {
                $this->pluginVersionFactory = $pluginVersionFactory;
            }

            public function getPluginVersion(string $name, string $versionConstraint): PluginVersionInterface
            {
                if ($name !== 'superplugin') {
                    throw new PluginNotFoundException($name);
                }

                return match ($versionConstraint) {
                    '1.0.0.0' => ($this->pluginVersionFactory)(),
                    default => throw new PluginVersionNotFoundException($name, $versionConstraint),
                };
            }
        };

        $this->assertTrue($class->hasPluginVersion('superplugin', '1.0.0.0'));
        $this->assertFalse($class->hasPluginVersion('superplugin', '1.0.0.1'));
    }
}
