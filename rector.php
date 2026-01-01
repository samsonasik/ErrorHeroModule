<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;

return RectorConfig::configure()
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
    )
    ->withPaths([__DIR__ . '/config', __DIR__ . '/src', __DIR__ . '/spec'])
    ->withRootFiles()
    ->withImportNames(removeUnusedImports: true)
    ->withSkip([
        __DIR__ . '/src/Controller',
        __DIR__ . '/src/Command/Preview',
        __DIR__ . '/src/Middleware/Routed/Preview',
        ArrayToFirstClassCallableRector::class,
    ]);
