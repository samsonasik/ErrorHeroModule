<?php

declare(strict_types=1);

namespace ErrorHeroModule\Middleware;

use Doctrine\ORM\EntityManager;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Transformer\Doctrine;
use ErrorHeroModule\Transformer\SymfonyService;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Zend\Expressive\Template\TemplateRendererInterface;

class ExpressiveFactory
{
    private function createMiddlewareInstance(ContainerInterface $container, array $configuration) : Expressive
    {
        return new Expressive(
            $configuration['error-hero-module'],
            $container->get(Logging::class),
            $container->get(TemplateRendererInterface::class)
        );
    }

    public function __invoke(ContainerInterface $container) : Expressive
    {
        $configuration = $container->get('config');

        if ($container->has(EntityManager::class) && ! isset($configuration['db'])) {
            return $this->createMiddlewareInstance(
                Doctrine::transform($container, $configuration),
                $configuration
            );
        }

        if ($container instanceof SymfonyContainerBuilder) {
            $configuration = (array) $configuration;
            if (! isset($configuration['db'])) {
                throw new RuntimeException('db config is required for build "ErrorHeroModuleLogger" service by Symfony Container');
            }

            return $this->createMiddlewareInstance(
                SymfonyService::transform($container, $configuration),
                $configuration
            );
        }

        return $this->createMiddlewareInstance($container, $configuration);
    }
}
