<?php

declare(strict_types=1);

namespace ErrorHeroModule\Transformer;

use Closure;
use Laminas\Log\Logger;
use Northwoods\Container\InjectorContainer;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

class AurynService extends TransformerAbstract implements TransformerInterface
{
    public static function transform(ContainerInterface $container, array $configuration): ContainerInterface
    {
        Assert::isInstanceOf($container, InjectorContainer::class);

        $dbAdapterConfig = parent::getDbAdapterConfig($configuration);
        $logger          = parent::getLoggerInstance($configuration, $dbAdapterConfig);

        $injector = &Closure::bind(static fn&($container) => $container->injector, null, $container)($container);
        $injector->delegate('ErrorHeroModuleLogger', fn(): Logger => $logger);

        return $container;
    }
}
