<?php

declare(strict_types=1);

namespace ErrorHeroModule\Transformer;

use Closure;
use Northwoods\Container\InjectorContainer as AurynInjectorContainer;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

class AurynService extends TransformerAbstract implements TransformerInterface
{
    public static function transform(ContainerInterface $container, array $configuration) : ContainerInterface
    {
        Assert::isInstanceOf($container, AurynInjectorContainer::class);

        $dbAdapterConfig = parent::getDbAdapterConfig($configuration);
        $logger          = parent::getLoggerInstance($configuration, $dbAdapterConfig);

        $injector = & Closure::bind(function & ($container) {
            return $container->injector;
        }, null, $container)($container);
        $injector->delegate('ErrorHeroModuleLogger', function () use ($logger) {
            return $logger;
        });

        return $container;
    }
}
