<?php

declare(strict_types=1);

namespace Plugin\Config;

use Phpcq\Exception\InvalidConfigException;
use Phpcq\Plugin\Config\ArrayConfigOption;
use Phpcq\Plugin\Config\BoolConfigOption;
use Phpcq\Plugin\Config\ConfigOptionInterface;
use PHPUnit\Framework\TestCase;

final class BoolConfigOptionTest extends TestCase
{
    public function testInstantiation(): void
    {
        $option = new BoolConfigOption('param', 'Param description', false, true);
        $this->assertInstanceOf(ConfigOptionInterface::class, $option);
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

    public function testValidateValue() : void
    {
        $this->expectNotToPerformAssertions();

        $option = new BoolConfigOption('param', 'Param description', false, false);
        $option->validateValue(true);
        $option->validateValue(false);
        $option->validateValue(null);
    }

    public function testThrowsOnInvalidValue() : void
    {
        $this->expectException(InvalidConfigException::class);

        $option = new BoolConfigOption('param', 'Param description', false, false);
        $option->validateValue('1');
    }

    public function testThrowsOnRequiredValue() : void
    {
        $this->expectException(InvalidConfigException::class);

        $option = new ArrayConfigOption('param', 'Param description', ['bar'], true);
        $option->validateValue(null);
    }
}
