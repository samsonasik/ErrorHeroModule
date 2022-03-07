<?php

declare(strict_types=1);

namespace ErrorHeroModule\Transformer;

use Closure;
use Pimple\Psr11\Container;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

class PimpleService extends TransformerAbstract implements TransformerInterface
{
    public static function transform(ContainerInterface $container, array $configuration): ContainerInterface
    {
        Assert::isInstanceOf($container, Container::class);

        $dbAdapterConfig = parent::getDbAdapterConfig($configuration);
        $logger          = parent::getLoggerInstance($configuration, $dbAdapterConfig);

        $pimple                          = &Closure::bind(
            static fn &($container) => $container->pimple,
            null,
            $container
        )($container);
        $pimple['ErrorHeroModuleLogger'] = $logger;

        return $container;
    }
}
