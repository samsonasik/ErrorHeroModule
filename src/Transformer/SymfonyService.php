<?php

declare(strict_types=1);

namespace ErrorHeroModule\Transformer;

use Assert\Assertion;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;

class SymfonyService extends TransformerAbstract implements TransformerInterface
{
    public static function transform(ContainerInterface $container, array $configuration) : ContainerInterface
    {
        Assertion::isInstanceOf($container, SymfonyContainerBuilder::class);

        $dbAdapterConfig = parent::getDbAdapterConfig($configuration);
        $logger          = parent::getLoggerInstance($configuration, $dbAdapterConfig);

        $container->set('ErrorHeroModuleLogger', $logger);

        return $container;
    }
}