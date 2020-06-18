<?php

declare(strict_types=1);

namespace Phpcq\Test\Plugin\Config;

use Phpcq\Plugin\Config\BoolConfigOption;
use Phpcq\PluginApi\Version10\ConfigurationOptionInterface;
use Phpcq\PluginApi\Version10\InvalidConfigException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Plugin\Config\AbstractConfigurationOption
 * @covers \Phpcq\Plugin\Config\BoolConfigOption
 */
final class BoolConfigOptionTest extends TestCase
{
    public function testInstantiation(): void
    {
        $option = new BoolConfigOption('param', 'Param description', false, true);
        $this->assertInstanceOf(ConfigurationOptionInterface::class, $option);
    }

    public function testInformation(): void
    {
        $option = new BoolConfigOption('param', 'Param description', false, true);

        $this->assertSame('param', $option->getName());
        $this->assertSame('bool', $option->getType());
        $this->assertSame('Param description', $option->getDescription());
        $this->assertFalse($option->getDefaultValue());
        $this->assertTrue($option->isRequired());

        $option = new BoolConfigOption('param2', 'Param2 description', true, false);

        $this->assertSame('param2', $option->getName());
        $this->assertSame('bool', $option->getType());
        $this->assertSame('Param2 description', $option->getDescription());
        $this->assertTrue($option->getDefaultValue());
        $this->assertFalse($option->isRequired());
    }

    public function testValidateValue(): void
    {
        $option = new BoolConfigOption('param', 'Param description', false, false);
        $option->validateValue(true);
        $option->validateValue(false);
        $option->validateValue(null);
        // Add to assertions that we passed validation.
        $this->addToAssertionCount(3);
    }

    public function testThrowsOnInvalidValue(): void
    {
        $this->expectException(InvalidConfigException::class);

        $option = new BoolConfigOption('param', 'Param description', false, false);
        $option->validateValue('1');
    }

    public function testThrowsOnRequiredValue(): void
    {
        $this->expectException(InvalidConfigException::class);

        $option = new BoolConfigOption('param', 'Param description', true, true);
        $option->validateValue(null);
    }
}
