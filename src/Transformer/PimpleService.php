<?php

declare(strict_types=1);

namespace ErrorHeroModule\Transformer;

use Assert\Assertion;
use Closure;
use Pimple\Psr11\Container as Psr11PimpleContainer;
use Psr\Container\ContainerInterface;

class PimpleService extends TransformerAbstract implements TransformerInterface
{
    public static function transform(ContainerInterface $container, array $configuration) : ContainerInterface
    {
        Assertion::isInstanceOf($container, Psr11PimpleContainer::class);

        $dbAdapterConfig = parent::getDbAdapterConfig($configuration);
        $logger          = parent::getLoggerInstance($configuration, $dbAdapterConfig);

        $pimple = & Closure::bind(function & ($container) {
            return $container->pimple;
        }, null, $container)($container);
        $pimple['ErrorHeroModuleLogger'] = $logger;

        return $container;
    }
}