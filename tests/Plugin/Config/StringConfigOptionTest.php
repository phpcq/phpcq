<?php

declare(strict_types=1);

namespace Plugin\Config;

use Phpcq\Exception\InvalidConfigException;
use Phpcq\Plugin\Config\ArrayConfigOption;
use Phpcq\Plugin\Config\StringConfigOption;
use Phpcq\Plugin\Config\ConfigOptionInterface;
use PHPUnit\Framework\TestCase;

final class StringConfigOptionTest extends TestCase
{
    public function testInstantiation(): void
    {
        $option = new StringConfigOption('param', 'Param description', 'foo', true);
        $this->assertInstanceOf(ConfigOptionInterface::class, $option);
    }

    public function testInformation(): void
    {
        $option = new StringConfigOption('param', 'Param description', 'foo', true);

        $this->assertSame('param', $option->getName());
        $this->assertSame('string', $option->getType());
        $this->assertSame('Param description', $option->getDescription());
        $this->assertSame('foo', $option->getDefaultValue());
        $this->assertTrue($option->isRequired());

        $option = new StringConfigOption('param2', 'Param2 description', 'bar', false);

        $this->assertSame('param2', $option->getName());
        $this->assertSame('string', $option->getType());
        $this->assertSame('Param2 description', $option->getDescription());
        $this->assertSame('bar', $option->getDefaultValue());
        $this->assertFalse($option->isRequired());
    }

    public function testValidateValue() : void
    {
        $this->expectNotToPerformAssertions();

        $option = new StringConfigOption('param', 'Param description', 'foo', false);
        $option->validateValue('bar');
        $option->validateValue(null);
    }

    public function testThrowsOnInvalidValue() : void
    {
        $this->expectException(InvalidConfigException::class);

        $option = new StringConfigOption('param', 'Param description', 'foo', true);
        $option->validateValue(1);
    }

    public function testThrowsOnRequiredValue() : void
    {
        $this->expectException(InvalidConfigException::class);

        $option = new ArrayConfigOption('param', 'Param description', ['bar'], true);
        $option->validateValue(null);
    }
}
