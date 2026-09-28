<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config;

use Phpcq\Runner\Config\TasksConfigBuilder;
use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Config\TasksConfigBuilder
 * @covers \Phpcq\Runner\Config\Builder\TaskConfigBuilder
 */
final class TasksConfigBuilderTest extends TestCase
{
    public function testProcessesFullTaskConfig(): void
    {
        $task = [
            'plugin'      => 'phpcs',
            'directories' => ['src'],
            'fix-stage'   => 'format',
            'config'      => ['standard' => 'PSR12', 'nested' => ['any' => null]],
            'uses'        => ['enricher-a' => null, 'enricher-b' => ['strict' => true]],
        ];

        $tasks = (new TasksConfigBuilder())->processConfig(['phpcs' => $task]);

        self::assertSame($task, $tasks['phpcs']);
    }

    public function testNormalizesEmptyTask(): void
    {
        $tasks = (new TasksConfigBuilder())->processConfig(['phpunit' => null]);

        self::assertSame(['config' => []], $tasks['phpunit']);
    }

    public function testNormalizesSimplifiedChainConfig(): void
    {
        $tasks = (new TasksConfigBuilder())->processConfig(['analyze' => ['phpcs', 'psalm']]);

        self::assertSame(['plugin' => 'chain', 'config' => ['tasks' => ['phpcs', 'psalm']]], $tasks['analyze']);
    }

    public function testAddsDefaultTask(): void
    {
        $tasks = (new TasksConfigBuilder())->processConfig(['phpcs' => null, 'psalm' => null]);

        self::assertSame(['plugin' => 'chain', 'config' => ['tasks' => ['phpcs', 'psalm']]], $tasks['default']);
    }

    public function testKeepsConfiguredDefaultTask(): void
    {
        $tasks = (new TasksConfigBuilder())->processConfig(['default' => ['phpcs'], 'phpcs' => null]);

        self::assertSame(['plugin' => 'chain', 'config' => ['tasks' => ['phpcs']]], $tasks['default']);
    }

    public function testAcceptsMissingTasks(): void
    {
        $tasks = (new TasksConfigBuilder())->processConfig(null);

        self::assertSame(['default' => ['plugin' => 'chain', 'config' => ['tasks' => []]]], $tasks);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function invalidConfigurationProvider(): iterable
    {
        yield 'tasks no array' => ['foo', 'tasks'];
        yield 'task no array' => [['cs' => 'foo'], 'tasks.cs'];
        yield 'unexpected key' => [['cs' => ['fix_stage' => 'format']], 'tasks.cs.fix_stage'];
        yield 'invalid plugin' => [['cs' => ['plugin' => 1]], 'tasks.cs.plugin'];
        yield 'invalid directories' => [['cs' => ['directories' => 'src']], 'tasks.cs.directories'];
        yield 'invalid fix stage' => [['cs' => ['fix-stage' => 'late']], 'tasks.cs.fix-stage'];
        yield 'invalid config' => [['cs' => ['config' => 'foo']], 'tasks.cs.config'];
        yield 'invalid uses' => [['cs' => ['uses' => 'foo']], 'tasks.cs.uses'];
        yield 'invalid enricher config' => [['cs' => ['uses' => ['enricher' => 'foo']]], 'tasks.cs.uses'];
    }

    #[DataProvider('invalidConfigurationProvider')]
    public function testReportsErrorPath(mixed $config, string $path): void
    {
        $this->expectException(ConfigurationValidationErrorException::class);
        $this->expectExceptionMessage('Configuration validation failed at path "' . $path . '"');

        (new TasksConfigBuilder())->processConfig($config);
    }
}
