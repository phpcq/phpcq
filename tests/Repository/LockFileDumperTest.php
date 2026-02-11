<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Runner\Repository;

use Phpcq\RepositoryDefinition\Plugin\PluginHash;
use Phpcq\RepositoryDefinition\Plugin\PluginRequirements;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\RepositoryDefinition\Tool\ToolRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\RepositoryDefinition\VersionRequirement;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Repository\InstalledRepositoryDumper;
use Phpcq\Runner\Test\TemporaryFileProducingTestTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Phpcq\Runner\Repository\LockFileDumper
 */
final class LockFileDumperTest extends TestCase
{
    use TemporaryFileProducingTestTrait;

    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    public function testLockFileDump(): void
    {
        $repository = new InstalledRepository();

        $pluginRequirements = new PluginRequirements();
        $pluginRequirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.3'));
        $installedPlugin = $this->createMock(PluginVersionInterface::class);
        $installedPlugin->method('getName')->willReturn('plugin-name');
        $installedPlugin->method('getApiVersion')->willReturn('1.0.0');
        $installedPlugin->method('getVersion')->willReturn('2.0.0');
        $installedPlugin->method('getFilePath')->willReturn('https://example.org/plugin-code.php');
        $installedPlugin->method('getSignaturePath')->willReturn('https://example.org/plugin-code.php.asc');
        $installedPlugin->method('getHash')->willReturn(PluginHash::createForString('plugin-name'));
        $installedPlugin->method('getRequirements')->willReturn($pluginRequirements);
        $repository->addPlugin(new InstalledPlugin($installedPlugin));

        $pluginRequirements = new PluginRequirements();
        $pluginRequirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.3'));
        $installedPlugin = $this->createMock(PluginVersionInterface::class);
        $installedPlugin->method('getName')->willReturn('plugin-name2');
        $installedPlugin->method('getApiVersion')->willReturn('1.0.0');
        $installedPlugin->method('getVersion')->willReturn('3.0.0');
        $installedPlugin->method('getFilePath')->willReturn('https://example.org/plugin-code2.php');
        $installedPlugin->method('getSignaturePath')->willReturn('https://example.org/plugin-code2.php.asc');
        $installedPlugin->method('getHash')->willReturn(PluginHash::createForString('plugin-name2'));
        $installedPlugin->method('getRequirements')->willReturn($pluginRequirements);

        $toolRequirements = new ToolRequirements();
        $toolRequirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.3'));

        $toolForPlugin = $this->createMock(ToolVersionInterface::class);
        $toolForPlugin->method('getName')->willReturn('tool1');
        $toolForPlugin->method('getVersion')->willReturn('1.0.0');
        $toolForPlugin->method('getPharUrl')->willReturn('https://example.org/tool-code2.phar');
        $toolForPlugin->method('getHash')->willReturn(ToolHash::createForString('tool1'));
        $toolForPlugin->method('getSignatureUrl')->willReturn('https://example.org/tool-code2.phar.asc');
        $toolForPlugin->method('getRequirements')->willReturn($toolRequirements);

        $repository->addPlugin(new InstalledPlugin($installedPlugin, ['tool1' => $toolForPlugin]));

        $fileName = tempnam(self::$tempdir, 'phpcq-test');

        $dumper = new InstalledRepositoryDumper(new Filesystem());
        $dumper->dump($repository, $fileName);

        $data = json_decode(file_get_contents($fileName), true, JSON_THROW_ON_ERROR);
        $this->assertSame(
            [
                'plugins' => [
                    'plugin-name' => [
                        'api-version'  => '1.0.0',
                        'version'      => '2.0.0',
                        'type'         => 'php-file',
                        'url'          => 'https://example.org/plugin-code.php',
                        'signature'    => 'https://example.org/plugin-code.php.asc',
                        'requirements' => [
                            'php' => [
                                'php' => '^7.3',
                            ],
                        ],
                        'checksum' => [
                            'type'  => PluginHash::SHA_512,
                            'value' =>
                                '1d2e73a06f2883832c702f2888e7b1795b892698b8f4363f84d170970a0c9d2f8d2f923fd2c0582fa6ce' .
                                '6f0dfeffdc3b296d016a35f517995e2c30dd83eef4f5',
                        ],
                        'tools' => [],
                    ],
                    'plugin-name2' => [
                        'api-version'  => '1.0.0',
                        'version'      => '3.0.0',
                        'type'         => 'php-file',
                        'url'          => 'https://example.org/plugin-code2.php',
                        'signature'    => 'https://example.org/plugin-code2.php.asc',
                        'requirements' => [
                            'php' => [
                                'php' => '^7.3',
                            ],
                        ],
                        'checksum' => [
                            'type'  => PluginHash::SHA_512,
                            'value' =>
                                '28ee6ad4daf9c8549b46390019a4cd285b3fdfaf8699a6863a7171f58d518e5339b65d08ab3650584070' .
                                '88e1c38a1ff5412f7c7efac101d57fb183ceb6ad12b1',
                        ],
                        'tools' => [
                            'tool1' => [
                                'version' => '1.0.0',
                                'url' => 'https://example.org/tool-code2.phar',
                                'requirements' => [
                                    'php' => [
                                        'php' => '^7.3',
                                    ],
                                ],
                                'checksum' => [
                                    'type'  => ToolHash::SHA_512,
                                    'value' =>
                                        '358882523c47fbb7eb7ca4cf20a2e069efcdfdf2e0ef43490d72d674bf959965a28ef6724341' .
                                        'f8fcf93dcdfc70f0390a1c99601bdaef1c20d49895e7ad69431f',
                                ],
                                'signature' => 'https://example.org/tool-code2.phar.asc',
                            ],
                        ],
                    ],
                ],
                'tools' => [],
            ],
            $data
        );
    }
}
