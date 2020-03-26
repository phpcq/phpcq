<?php

declare(strict_types=1);

namespace Plugin\Config;

use Phpcq\Exception\InvalidConfigException;
use Phpcq\Plugin\Config\ConfigOptionInterface;
use Phpcq\Plugin\Config\ConfigOptions;
use Phpcq\Plugin\Config\PhpcqConfigOptionsBuilder;

final class ConfigOptionsTest
{
    public function testValidateConfig(): void
    {
        $mock1    = $this->createMock(ConfigOptionInterface::class);
        $mock2    = $this->createMock(ConfigOptionInterface::class);
        $instance = new ConfigOptions([$mock1, $mock2]);

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

        $instance->describeOption($mock1);
        $instance->describeOption($mock2);

        $instance->validateConfig(['foo' => 'bar', 'baz' => 1]);
    }

    public function testUnknownConfigKeys(): void
    {
        $mock1    = $this->createMock(ConfigOptionInterface::class);
        $mock2    = $this->createMock(ConfigOptionInterface::class);
        $instance = new ConfigOptions([$mock1, $mock2]);

        $mock1
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('foo');

        $mock2
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('baz');

        $instance->describeOption($mock1);
        $instance->describeOption($mock2);

        $this->expectException(InvalidConfigException::class);
        $instance->validateConfig(['foo' => 'bar', 'baz' => 1, 'bar' => false]);
    }

    public function testInvalidConfig(): void
    {
        $mock1    = $this->createMock(ConfigOptionInterface::class);
        $mock2    = $this->createMock(ConfigOptionInterface::class);
        $instance = new ConfigOptions([$mock1, $mock2]);

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

        $instance->describeOption($mock1);
        $instance->describeOption($mock2);

        $this->expectException(InvalidConfigException::class);
        $instance->validateConfig(['foo' => 'bar', 'baz' => 1]);
    }
}
