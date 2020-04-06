<?php

declare(strict_types=1);

namespace Plugin\Config;

use Phpcq\Plugin\Config\ArrayConfigOption;
use Phpcq\Plugin\Config\BoolConfigOption;
use Phpcq\Plugin\Config\FloatConfigOption;
use Phpcq\Plugin\Config\IntConfigOption;
use Phpcq\Plugin\Config\PhpcqConfigurationOptionsBuilder;
use Phpcq\Plugin\Config\StringConfigOption;
use Phpcq\PluginApi\Version10\ConfigurationOptionInterface;
use Phpcq\PluginApi\Version10\ConfigurationOptionsBuilderInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Plugin\Config\PhpcqConfigurationOptionsBuilder
 */
final class PhpcqConfigOptionsBuilderTest extends TestCase
{
    public function testInstantiation(): void
    {
        $instance = new PhpcqConfigurationOptionsBuilder();
        $this->assertInstanceOf(ConfigurationOptionsBuilderInterface::class, $instance);
    }

    public function testDescribeArrayOption(): void
    {
        $instance = new PhpcqConfigurationOptionsBuilder();

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
        $instance = new PhpcqConfigurationOptionsBuilder();

        $this->assertSame($instance, $instance->describeBoolOption('foo', 'Foo Option', false, true));

        foreach ($instance->getOptions() as $option) {
            $this->assertInstanceOf(BoolConfigOption::class, $option);
            $this->assertSame('foo', $option->getName());
            $this->assertSame('Foo Option', $option->getDescription());
            $this->assertFalse($option->getDefaultValue());
            $this->assertTrue($option->isRequired());
        }
    }

    public function testDescribeIntOption(): void
    {
        $instance = new PhpcqConfigurationOptionsBuilder();

        $this->assertSame($instance, $instance->describeIntOption('foo', 'Foo Option', 1, true));

        foreach ($instance->getOptions() as $option) {
            $this->assertInstanceOf(IntConfigOption::class, $option);
            $this->assertSame('foo', $option->getName());
            $this->assertSame('Foo Option', $option->getDescription());
            $this->assertSame(1, $option->getDefaultValue());
            $this->assertTrue($option->isRequired());
        }
    }

    public function testDescribeStringOption(): void
    {
        $instance = new PhpcqConfigurationOptionsBuilder();

        $this->assertSame($instance, $instance->describeStringOption('foo', 'Foo Option', 'bar', true));

        foreach ($instance->getOptions() as $option) {
            $this->assertInstanceOf(StringConfigOption::class, $option);
            $this->assertSame('foo', $option->getName());
            $this->assertSame('Foo Option', $option->getDescription());
            $this->assertSame('bar', $option->getDefaultValue());
            $this->assertTrue($option->isRequired());
        }
    }

    public function testDescribeFloatOption(): void
    {
        $instance = new PhpcqConfigurationOptionsBuilder();

        $this->assertSame($instance, $instance->describeFloatOption('foo', 'Foo Option', 1.5, true));

        foreach ($instance->getOptions() as $option) {
            $this->assertInstanceOf(FloatConfigOption::class, $option);
            $this->assertSame('foo', $option->getName());
            $this->assertSame('Foo Option', $option->getDescription());
            $this->assertSame(1.5, $option->getDefaultValue());
            $this->assertTrue($option->isRequired());
        }
    }

    public function testDescribeOption(): void
    {
        $instance = new PhpcqConfigurationOptionsBuilder();
        $mock     = $this->createMock(ConfigurationOptionInterface::class);

        $instance->describeOption($mock);

        foreach ($instance->getOptions() as $option) {
            $this->assertSame($mock, $option);
        }
    }

    public function testGetOptions(): void
    {
        $instance = new PhpcqConfigurationOptionsBuilder();
        $mock1    = $this->createMock(ConfigurationOptionInterface::class);
        $mock2    = $this->createMock(ConfigurationOptionInterface::class);

        $mock1
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('foo');

        $mock2
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('bar');

        $instance->describeOption($mock1);
        $instance->describeOption($mock2);

        $this->assertCount(2, $instance->getOptions());
    }
}
