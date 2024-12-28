<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Strict\Rector\BooleanNot\BooleanInBooleanNotRuleFixerRector;

return RectorConfig::configure()
    ->withPhpSets()
    ->withPreparedSets(
        codeQuality: true,
        codingStyle: true,
        deadCode: true,
        naming: true,
        privatization: true,
        typeDeclarations: true,
        strictBooleans: true
    )
    ->withPaths([__DIR__ . '/config', __DIR__ . '/src', __DIR__ . '/spec'])
    ->withRootFiles()
    ->withImportNames(removeUnusedImports: true)
    ->withSkip([
        __DIR__ . '/src/Controller',
        __DIR__ . '/src/Command/Preview',
        __DIR__ . '/src/Middleware/Routed/Preview',
        FirstClassCallableRector::class,
        BooleanInBooleanNotRuleFixerRector::class => [
            __DIR__ . '/src/Handler/Logging.php',
        ],
    ]);
