<?php

declare(strict_types=1);

namespace ErrorHeroModule\Transformer;

use DI\Container as PHPDIContainer;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

class PHPDIService extends TransformerAbstract implements TransformerInterface
{
    public static function transform(ContainerInterface $container, array $configuration) : ContainerInterface
    {
        Assert::isInstanceOf($container, PHPDIContainer::class);

        $dbAdapterConfig = parent::getDbAdapterConfig($configuration);
        $logger          = parent::getLoggerInstance($configuration, $dbAdapterConfig);

        $container->set('ErrorHeroModuleLogger', $logger);

        return $container;
    }
}
