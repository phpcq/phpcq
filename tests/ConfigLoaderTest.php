<?php

declare(strict_types=1);

use Phpcq\ConfigLoader;

final class ConfigLoaderTest extends \PHPUnit\Framework\TestCase
{
    public function testFullFeaturedConfigFile(): void
    {
        $loader = new ConfigLoader(__DIR__ . '/fixtures/phpcq-demo.yaml');
        $config = $loader->getConfig();

        $this->assertArrayHasKey('directories', $config);
        $this->assertEquals(['src', 'tests'], $config['directories']);

        $this->assertArrayHasKey('repositories', $config);
        $this->assertEquals(
            [
                'https://example.com/build-tools/info.json',
                'https://example.com/build-tools2/info.json',
                'https://example.com/build-tools3/info.json'
            ],
            $config['repositories']
        );

        $this->assertArrayHasKey('artifact', $config);
        $this->assertEquals('.phpcq/build', $config['artifact']);

        $this->assertArrayHasKey('tools', $config);
        $this->assertEquals(
            [
                'phpunit' => [
                    'version' => '^7.0',
                ],
                'custom-task-tool' => [
                    'version' => '^1.0',
                ],
                'local-tool' => [
                    'runner-plugin' => '.phpcq/plugins/boot-local-tool.php'
                ],
            ],
            $config['tools']
        );
    }

    public function testMergeConfiguration(): void
    {
        $this->markTestSkipped();
    }

    public function testMissingPhpcqConfiguration(): void
    {
        $this->markTestSkipped();
    }
}
