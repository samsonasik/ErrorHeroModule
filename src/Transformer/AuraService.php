<?php

declare(strict_types=1);

namespace ErrorHeroModule\Transformer;

use Aura\Di\Container as AuraContainer;
use Closure;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

class AuraService extends TransformerAbstract implements TransformerInterface
{
    public static function transform(ContainerInterface $container, array $configuration): ContainerInterface
    {
        Assert::isInstanceOf($container, AuraContainer::class);

        $dbAdapterConfig = parent::getDbAdapterConfig($configuration);
        $logger          = parent::getLoggerInstance($configuration, $dbAdapterConfig);

        $containerLocked = &Closure::bind(static function & ($container) {
            return $container->locked;
        }, null, $container)($container);
        $containerLocked = false;

        return $container->set('ErrorHeroModuleLogger', $logger);
    }
}
