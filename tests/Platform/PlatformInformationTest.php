<?php

declare(strict_types=1);

namespace Platform;

use Phpcq\Platform\PlatformInformation;
use PHPUnit\Framework\TestCase;

class PlatformInformationTest extends TestCase
{
    public function testPhpVersion(): void
    {
        $platformInformation = new PlatformInformation();

        $this->assertSame(phpversion(), $platformInformation->getPhpVersion());
    }

    public function testExtensions(): void
    {
        $platformInformation = new PlatformInformation();
        $loadedExtensions = array_filter(
            get_loaded_extensions(),
            static function ($value) : bool {
                return !in_array($value, ['standard', 'Core']);
            }
        );

        $this->assertCount(count($loadedExtensions), $platformInformation->getExtensions());

    }

    public function testLibraries(): void
    {
        $this->markTestSkipped();
    }
}