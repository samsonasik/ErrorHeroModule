<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\CodingStyle\Rector\ArrowFunction\StaticArrowFunctionRector;
use Rector\CodingStyle\Rector\Closure\StaticClosureRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::NAMING,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION,
    ]);

    $rectorConfig->parallel();
    $rectorConfig->paths([__DIR__ . '/config', __DIR__ . '/src', __DIR__ . '/spec', __FILE__]);
    $rectorConfig->importNames();
    $rectorConfig->removeUnusedImports();
    $rectorConfig->skip([
        __DIR__ . '/src/Controller',
        __DIR__ . '/src/Command/Preview',
        __DIR__ . '/src/Middleware/Routed/Preview',
        CallableThisArrayToAnonymousFunctionRector::class,
        StaticArrowFunctionRector::class => [
            __DIR__ . '/spec',
        ],
        StaticClosureRector::class       => [
            __DIR__ . '/spec',
        ],
        FirstClassCallableRector::class,
    ]);
};
