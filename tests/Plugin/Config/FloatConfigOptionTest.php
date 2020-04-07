<?php

declare(strict_types=1);

namespace Plugin\Config;

use Phpcq\Plugin\Config\FloatConfigOption;
use Phpcq\PluginApi\Version10\ConfigurationOptionInterface;
use Phpcq\PluginApi\Version10\InvalidConfigException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Plugin\Config\AbstractConfigurationOption
 * @covers \Phpcq\Plugin\Config\FloatConfigOption
 */
final class FloatConfigOptionTest extends TestCase
{
    public function testInstantiation(): void
    {
        $option = new FloatConfigOption('param', 'Param description', 1.0, true);
        $this->assertInstanceOf(ConfigurationOptionInterface::class, $option);
    }

    public function testInformation(): void
    {
        $option = new FloatConfigOption('param', 'Param description', 2.0, true);

        $this->assertSame('param', $option->getName());
        $this->assertSame('float', $option->getType());
        $this->assertSame('Param description', $option->getDescription());
        $this->assertSame(2.0, $option->getDefaultValue());
        $this->assertTrue($option->isRequired());

        $option = new FloatConfigOption('param2', 'Param2 description', 1.4, false);

        $this->assertSame('param2', $option->getName());
        $this->assertSame('float', $option->getType());
        $this->assertSame('Param2 description', $option->getDescription());
        $this->assertSame(1.4, $option->getDefaultValue());
        $this->assertFalse($option->isRequired());
    }

    public function testValidateValue() : void
    {
        $option = new FloatConfigOption('param', 'Param description', 1.4, false);
        $option->validateValue(1.5);
        $option->validateValue(null);
        // Add to assertions that we passed validation.
        $this->addToAssertionCount(2);
    }

    public function testThrowsOnInvalidValue() : void
    {
        $this->expectException(InvalidConfigException::class);

        $option = new FloatConfigOption('param', 'Param description', 1.5, false);
        $option->validateValue(1);
    }

    public function testThrowsOnRequiredValue() : void
    {
        $this->expectException(InvalidConfigException::class);

        $option = new FloatConfigOption('param', 'Param description', 1.5, true);
        $option->validateValue(null);
    }
}
