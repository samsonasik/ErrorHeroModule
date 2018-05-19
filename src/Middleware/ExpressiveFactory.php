<?php

declare(strict_types=1);

namespace ErrorHeroModule\Middleware;

use Aura\Di\Container as AuraContainer;
use Doctrine\ORM\EntityManager;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Transformer\AuraService;
use ErrorHeroModule\Transformer\Doctrine;
use ErrorHeroModule\Transformer\SymfonyService;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\ServiceManager\ServiceManager;

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

    private function verifyConfig($configuration, $containerType = 'Symfony')
    {
        $configuration = (array) $configuration;
        if (! isset($configuration['db'])) {
            throw new RuntimeException(
                sprintf(
                    'db config is required for build "ErrorHeroModuleLogger" service by %s Container',
                    $containerType
                )
            );
        }

        return $configuration;
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

        if ($container instanceof ServiceManager) {
            return $this->createMiddlewareInstance($container, $configuration);
        }

        if ($container instanceof SymfonyContainerBuilder) {
            $configuration = $this->verifyConfig($configuration, 'Symfony');

            return $this->createMiddlewareInstance(
                SymfonyService::transform($container, $configuration),
                $configuration
            );
        }

        if ($container instanceof AuraContainer) {
            $configuration = $this->verifyConfig($configuration, 'Aura');

            return $this->createMiddlewareInstance(
                AuraService::transform($container, $configuration),
                $configuration
            );
        }

        throw new RuntimeException(sprintf(
            'container "%s" is unsupported',
            get_class($container)
        ));
    }
}
