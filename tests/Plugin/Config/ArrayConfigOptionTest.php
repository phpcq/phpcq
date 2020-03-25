<?php

declare(strict_types=1);

namespace Plugin\Config;

use Phpcq\Exception\InvalidConfigException;
use Phpcq\Plugin\Config\ArrayConfigOption;
use Phpcq\Plugin\Config\ConfigOptionInterface;
use PHPUnit\Framework\TestCase;

final class ArrayConfigOptionTest extends TestCase
{
    public function testInstantiation(): void
    {
        $option = new ArrayConfigOption('param', 'Param description', ['foo', 'bar'], true);
        $this->assertInstanceOf(ConfigOptionInterface::class, $option);
    }

    public function testInformation(): void
    {
        $option = new ArrayConfigOption('param', 'Param description', ['foo', 'bar'], true);

        $this->assertSame('param', $option->getName());
        $this->assertSame('array', $option->getType());
        $this->assertSame('Param description', $option->getDescription());
        $this->assertSame(['foo', 'bar'], $option->getDefaultValue());
        $this->assertTrue($option->isRequired());

        $option = new ArrayConfigOption('param2', 'Param2 description', ['baz'], false);

        $this->assertSame('param2', $option->getName());
        $this->assertSame('array', $option->getType());
        $this->assertSame('Param2 description', $option->getDescription());
        $this->assertSame(['baz'], $option->getDefaultValue());
        $this->assertFalse($option->isRequired());
    }

    public function testValidateValue() : void
    {
        $this->expectNotToPerformAssertions();

        $option = new ArrayConfigOption('param', 'Param description', ['bar'], false);
        $option->validateValue(['bar']);
        $option->validateValue(['foo' => 'bar']);
        $option->validateValue(['foo' => 'bar', 1 => 'baz']);
        $option->validateValue(null);
    }

    public function testThrowsOnInvalidValue() : void
    {
        $this->expectException(InvalidConfigException::class);

        $option = new ArrayConfigOption('param', 'Param description', ['bar'], false);
        $option->validateValue('1');
    }

    public function testThrowsOnRequiredValue() : void
    {
        $this->expectException(InvalidConfigException::class);

        $option = new ArrayConfigOption('param', 'Param description', ['bar'], true);
        $option->validateValue(null);
    }
}
