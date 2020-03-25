<?php

declare(strict_types=1);

namespace Plugin\Config;

use Phpcq\Exception\InvalidConfigException;
use Phpcq\Plugin\Config\ArrayConfigOption;
use Phpcq\Plugin\Config\BoolConfigOption;
use Phpcq\Plugin\Config\ConfigOptionInterface;
use Phpcq\Plugin\Config\ConfigOptionsBuilderInterface;
use Phpcq\Plugin\Config\IntConfigOption;
use Phpcq\Plugin\Config\PhpcqConfigOptionsBuilder;
use Phpcq\Plugin\Config\StringConfigOption;
use PHPUnit\Framework\TestCase;

final class PhpcqConfigOptionsBuilderTest extends TestCase
{
    public function testInstantiation(): void
    {
        $instance = new PhpcqConfigOptionsBuilder();
        $this->assertInstanceOf(ConfigOptionsBuilderInterface::class, $instance);
    }

    public function testDescribeArrayOption(): void
    {
        $instance = new PhpcqConfigOptionsBuilder();

        $this->assertSame($instance, $instance->describeArrayOption('foo', 'Foo Option', ['bar'], true));

        foreach ($instance->getOptions() as $option) {
            $this->assertInstanceOf(ArrayConfigOption::class, $option);
            $this->assertSame('foo', $option->getName());
            $this->assertSame('Foo Option', $option->getDescription());
            $this->assertSame(['bar'], $option->getDefaultValue());
            $this->assertTrue($option->isRequired());
        }
    }

    public function testDescribeBoolOption(): void
    {
        $instance = new PhpcqConfigOptionsBuilder();

        $this->assertSame($instance, $instance->describeBoolOption('foo', 'Foo Option', false, true));

        foreach ($instance->getOptions() as $option) {
            $this->assertInstanceOf(BoolConfigOption::class, $option);
            $this->assertSame('foo', $option->getName());
            $this->assertSame('Foo Option', $option->getDescription());
            $this->assertFalse($option->getDefaultValue());
            $this->assertTrue($option->isRequired());
        }
    }

    public function testIntBoolOption(): void
    {
        $instance = new PhpcqConfigOptionsBuilder();

        $this->assertSame($instance, $instance->describeIntOption('foo', 'Foo Option', 1, true));

        foreach ($instance->getOptions() as $option) {
            $this->assertInstanceOf(IntConfigOption::class, $option);
            $this->assertSame('foo', $option->getName());
            $this->assertSame('Foo Option', $option->getDescription());
            $this->assertSame(1, $option->getDefaultValue());
            $this->assertTrue($option->isRequired());
        }
    }

    public function testStringBoolOption(): void
    {
        $instance = new PhpcqConfigOptionsBuilder();

        $this->assertSame($instance, $instance->describeStringOption('foo', 'Foo Option', 'bar', true));

        foreach ($instance->getOptions() as $option) {
            $this->assertInstanceOf(StringConfigOption::class, $option);
            $this->assertSame('foo', $option->getName());
            $this->assertSame('Foo Option', $option->getDescription());
            $this->assertSame('bar', $option->getDefaultValue());
            $this->assertTrue($option->isRequired());
        }
    }

    public function testDescribeOption(): void
    {
        $instance = new PhpcqConfigOptionsBuilder();
        $mock     = $this->createMock(ConfigOptionInterface::class);

        $instance->describeOption($mock);

        foreach ($instance->getOptions() as $option) {
            $this->assertSame($mock, $option);
        }
    }

    public function testGetOptions(): void
    {
        $instance = new PhpcqConfigOptionsBuilder();
        $mock1    = $this->createMock(ConfigOptionInterface::class);
        $mock2    = $this->createMock(ConfigOptionInterface::class);

        $mock1
            ->expects($this->once())
            ->method('getName')
            ->willReturn('foo');

        $mock2
            ->expects($this->once())
            ->method('getName')
            ->willReturn('bar');

        $instance->describeOption($mock1);
        $instance->describeOption($mock2);

        $this->assertEquals([$mock1, $mock2], $instance->getOptions());
    }

    public function testValidateConfig(): void
    {
        $instance = new PhpcqConfigOptionsBuilder();
        $mock1    = $this->createMock(ConfigOptionInterface::class);
        $mock2    = $this->createMock(ConfigOptionInterface::class);

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
        $instance = new PhpcqConfigOptionsBuilder();
        $mock1    = $this->createMock(ConfigOptionInterface::class);
        $mock2    = $this->createMock(ConfigOptionInterface::class);

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
        $instance = new PhpcqConfigOptionsBuilder();
        $mock1    = $this->createMock(ConfigOptionInterface::class);
        $mock2    = $this->createMock(ConfigOptionInterface::class);

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
