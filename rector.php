<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Privatization\Rector\Class_\FinalizeTestCaseClassRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\Class_\AddTestsVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()
    ->withRules([
        FinalizeTestCaseClassRector::class,
        AddTestsVoidReturnTypeWhereNoReturnRector::class,
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
    ])
    ->withSkip([
        __DIR__ . '/src/Platform/PlatformInformation.php',
        ClosureToArrowFunctionRector::class => [
            __DIR__ . '/src/Report/Writer/DiagnosticIterator.php'
        ],
        ChangeSwitchToMatchRector::class => [
            // Better reflection does not work with a constant used here
            __DIR__ . '/tests/Platform/PlatformInformationTest.php'
        ]
    ]);
