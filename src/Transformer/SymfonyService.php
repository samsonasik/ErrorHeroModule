<?php

declare(strict_types=1);

namespace ErrorHeroModule\Transformer;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webmozart\Assert\Assert;

final class SymfonyService extends TransformerAbstract implements TransformerInterface
{
    public static function transform(ContainerInterface $container, array $configuration): ContainerInterface
    {
        Assert::isInstanceOf($container, ContainerBuilder::class);

        $dbAdapterConfig = parent::getDbAdapterConfig($configuration);
        $logger          = parent::getLoggerInstance($configuration, $dbAdapterConfig);

        $container->set('ErrorHeroModuleLogger', $logger);

        return $container;
    }
}
