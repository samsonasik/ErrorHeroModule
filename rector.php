<?php

use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(SetList::PHP_74);
    $containerConfigurator->import(SetList::PHP_80);

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [__DIR__ . '/config', __DIR__ . '/src', __DIR__ . '/spec']);

    $parameters->set(Option::AUTO_IMPORT_NAMES, true);
    $parameters->set(Option::ENABLE_CACHE, true);
    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_80);

    $parameters->set(Option::SKIP, [
        TypedPropertyRector::class => [
            __DIR__ . '/src/Listener/Mvc.php',
            __DIR__ . '/src/Middleware/Mezzio.php',
        ],
    ]);
};
