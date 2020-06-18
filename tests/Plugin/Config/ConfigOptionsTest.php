<?php

declare(strict_types=1);

namespace Phpcq\Test\Plugin\Config;

use Phpcq\Plugin\Config\ConfigOptions;
use Phpcq\PluginApi\Version10\ConfigurationOptionInterface;
use Phpcq\PluginApi\Version10\InvalidConfigException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Plugin\Config\ConfigOptions
 */
final class ConfigOptionsTest extends TestCase
{
    public function testValidateConfig(): void
    {
        $mock1 = $this->createMock(ConfigurationOptionInterface::class);
        $mock2 = $this->createMock(ConfigurationOptionInterface::class);

        $mock1
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('foo');

        $mock1
            ->expects($this->once())
            ->method('validateValue');

        $mock2
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('baz');

        $mock2
            ->expects($this->once())
            ->method('validateValue');

        $instance = new ConfigOptions([$mock1, $mock2]);

        $instance->validateConfig(['foo' => 'bar', 'baz' => 1]);
    }

    public function testUnknownConfigKeys(): void
    {
        $mock1 = $this->createMock(ConfigurationOptionInterface::class);
        $mock2 = $this->createMock(ConfigurationOptionInterface::class);

        $mock1
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('foo');

        $mock2
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('baz');

        $instance = new ConfigOptions([$mock1, $mock2]);

        $this->expectException(InvalidConfigException::class);
        $instance->validateConfig(['foo' => 'bar', 'baz' => 1, 'bar' => false]);
    }

    public function testInvalidConfig(): void
    {
        $mock1 = $this->createMock(ConfigurationOptionInterface::class);
        $mock2 = $this->createMock(ConfigurationOptionInterface::class);

        $mock1
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('foo');

        $mock1
            ->expects($this->once())
            ->method('validateValue');

        $mock2
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('baz');

        $mock2
            ->expects($this->once())
            ->method('validateValue')
            ->willThrowException(new InvalidConfigException());

        $instance = new ConfigOptions([$mock1, $mock2]);

        $this->expectException(InvalidConfigException::class);
        $instance->validateConfig(['foo' => 'bar', 'baz' => 1]);
    }
}
