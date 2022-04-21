<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\CodingStyle\Rector\ClassMethod\UnSpreadOperatorRector;
use Rector\CodingStyle\Rector\Property\InlineSimplePropertyAnnotationRector;
use Rector\Config\RectorConfig;
use Rector\Privatization\Rector\Class_\FinalizeClassesWithoutChildrenRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_80,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::NAMING,
        SetList::PRIVATIZATION,
        SetList::PSR_4,
        SetList::TYPE_DECLARATION,
        SetList::TYPE_DECLARATION_STRICT,
    ]);
    $rectorConfig->rule(InlineSimplePropertyAnnotationRector::class);

    $rectorConfig->parallel();
    $rectorConfig->paths([__DIR__ . '/config', __DIR__ . '/src', __DIR__ . '/spec', __DIR__ . '/rector.php']);
    $rectorConfig->importNames();
    $rectorConfig->skip([
        __DIR__ . '/src/Controller',
        __DIR__ . '/src/Middleware/Routed/Preview',
        CallableThisArrayToAnonymousFunctionRector::class,
        UnSpreadOperatorRector::class,
        FinalizeClassesWithoutChildrenRector::class,
    ]);
};
