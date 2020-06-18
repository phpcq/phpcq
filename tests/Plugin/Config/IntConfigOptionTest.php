<?php

declare(strict_types=1);

namespace Phpcq\Test\Plugin\Config;

use Phpcq\Plugin\Config\IntConfigOption;
use Phpcq\PluginApi\Version10\ConfigurationOptionInterface;
use Phpcq\PluginApi\Version10\InvalidConfigException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Plugin\Config\AbstractConfigurationOption
 * @covers \Phpcq\Plugin\Config\IntConfigOption
 */
final class IntConfigOptionTest extends TestCase
{
    public function testInstantiation(): void
    {
        $option = new IntConfigOption('param', 'Param description', 1, true);
        $this->assertInstanceOf(ConfigurationOptionInterface::class, $option);
    }

    public function testInformation(): void
    {
        $option = new IntConfigOption('param', 'Param description', 2, true);

        $this->assertSame('param', $option->getName());
        $this->assertSame('int', $option->getType());
        $this->assertSame('Param description', $option->getDescription());
        $this->assertSame(2, $option->getDefaultValue());
        $this->assertTrue($option->isRequired());

        $option = new IntConfigOption('param2', 'Param2 description', 1, false);

        $this->assertSame('param2', $option->getName());
        $this->assertSame('int', $option->getType());
        $this->assertSame('Param2 description', $option->getDescription());
        $this->assertSame(1, $option->getDefaultValue());
        $this->assertFalse($option->isRequired());
    }

    public function testValidateValue(): void
    {
        $option = new IntConfigOption('param', 'Param description', 1, false);
        $option->validateValue(1);
        $option->validateValue(null);
        // Add to assertions that we passed validation.
        $this->addToAssertionCount(2);
    }

    public function testThrowsOnInvalidValue(): void
    {
        $this->expectException(InvalidConfigException::class);

        $option = new IntConfigOption('param', 'Param description', 1, false);
        $option->validateValue(1.5);
    }

    public function testThrowsOnRequiredValue(): void
    {
        $this->expectException(InvalidConfigException::class);

        $option = new IntConfigOption('param', 'Param description', 1, true);
        $option->validateValue(null);
    }
}
