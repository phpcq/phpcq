<?php

declare(strict_types=1);

namespace Phpcq\Test\Repository;

use Phpcq\Repository\BootstrapInterface;
use Phpcq\Repository\ToolInformation;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Repository\ToolInformation
 */
class ToolInformationTest extends TestCase
{
    public function testAddsVersionAndCanRetrieveVersion(): void
    {
        $bootstrap = $this->createMock(BootstrapInterface::class);
        $version = new ToolInformation('supertool', '1.0.0.0', 'http://phar.file/a.phar', ['php' => '^7.4'], $bootstrap);

        $this->assertSame('supertool', $version->getName());
        $this->assertSame('1.0.0.0', $version->getVersion());
        $this->assertSame('http://phar.file/a.phar', $version->getPharUrl());
        $this->assertSame($bootstrap, $version->getBootstrap());
        $this->assertSame(['php' => '^7.4'], $version->getPlatformRequirements());
    }
}