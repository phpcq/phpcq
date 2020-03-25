<?php

declare(strict_types=1);

namespace Plugin\Config;

use Phpcq\Exception\InvalidConfigException;
use Phpcq\Plugin\Config\ArrayConfigOption;
use Phpcq\Plugin\Config\IntConfigOption;
use Phpcq\Plugin\Config\ConfigOptionInterface;
use PHPUnit\Framework\TestCase;

final class IntConfigOptionTest extends TestCase
{
    public function testInstantiation(): void
    {
        $option = new IntConfigOption('param', 'Param description', 1, true);
        $this->assertInstanceOf(ConfigOptionInterface::class, $option);
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

    public function testValidateValue() : void
    {
        $this->expectNotToPerformAssertions();

        $option = new IntConfigOption('param', 'Param description', 1, false);
        $option->validateValue(1);
        $option->validateValue(null);
    }

    public function testThrowsOnInvalidValue() : void
    {
        $this->expectException(InvalidConfigException::class);

        $option = new IntConfigOption('param', 'Param description', 1, false);
        $option->validateValue(1.5);
    }

    public function testThrowsOnRequiredValue() : void
    {
        $this->expectException(InvalidConfigException::class);

        $option = new ArrayConfigOption('param', 'Param description', ['bar'], true);
        $option->validateValue(null);
    }
}
