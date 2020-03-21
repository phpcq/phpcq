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
        $option = new ArrayConfigOption('param', 'Param description', ['foo', 'bar']);
        $this->assertInstanceOf(ConfigOptionInterface::class, $option);
    }

    public function testInformation(): void
    {
        $option = new ArrayConfigOption('param', 'Param description', ['foo', 'bar']);

        $this->assertSame('param', $option->getName());
        $this->assertSame('array', $option->getType());
        $this->assertSame('Param description', $option->getDescription());
        $this->assertSame(['foo', 'bar'], $option->getDefaultValue());

        $option = new ArrayConfigOption('param2', 'Param2 description', ['baz']);

        $this->assertSame('param2', $option->getName());
        $this->assertSame('array', $option->getType());
        $this->assertSame('Param2 description', $option->getDescription());
        $this->assertSame(['baz'], $option->getDefaultValue());
    }

    public function testValidateValue() : void
    {
        $this->expectNotToPerformAssertions();

        $option = new ArrayConfigOption('param', 'Param description', ['bar']);
        $option->validateValue(['bar']);
        $option->validateValue(['foo' => 'bar']);
        $option->validateValue(['foo' => 'bar', 1 => 'baz']);
    }

    public function testThrowsOnInvalidValue() : void
    {
        $this->expectException(InvalidConfigException::class);

        $option = new ArrayConfigOption('param', 'Param description', ['bar']);
        $option->validateValue('1');
    }
}
