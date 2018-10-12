<?php

declare(strict_types=1);

namespace ErrorHeroModule\Middleware;

use Aura\Di\Container as AuraContainer;
use DI\Container as PHPDIContainer;
use Doctrine\ORM\EntityManager;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Transformer\AuraService;
use ErrorHeroModule\Transformer\AurynService;
use ErrorHeroModule\Transformer\Doctrine;
use ErrorHeroModule\Transformer\PHPDIService;
use ErrorHeroModule\Transformer\PimpleService;
use ErrorHeroModule\Transformer\SymfonyService;
use Northwoods\Container\InjectorContainer as AurynInjectorContainer;
use Pimple\Psr11\Container as Psr11PimpleContainer;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\ServiceManager\ServiceManager;

class ExpressiveFactory
{
    private const CONTAINERS_TRANSFORM = [
        SymfonyContainerBuilder::class => SymfonyService::class,
        AuraContainer::class           => AuraService::class,
        AurynInjectorContainer::class  => AurynService::class,
        Psr11PimpleContainer::class    => PimpleService::class,
        PHPDIContainer::class          => PHPDIService::class,
    ];

    private function createMiddlewareInstance(ContainerInterface $container, array $configuration) : Expressive
    {
        return new Expressive(
            $configuration['error-hero-module'],
            $container->get(Logging::class),
            $container->has(TemplateRendererInterface::class)
                ? $container->get(TemplateRendererInterface::class)
                : null
        );
    }

    private function verifyConfig($configuration, string $containerClass) : array
    {
        $configuration = (array) $configuration;
        if (! isset($configuration['db'])) {
            throw new RuntimeException(
                \sprintf(
                    'db config is required for build "ErrorHeroModuleLogger" service by %s Container',
                    $containerClass
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

        $containerClass = \get_class($container);
        if (\in_array($containerClass, \array_keys(self::CONTAINERS_TRANSFORM), true)) {
            $configuration = $this->verifyConfig($configuration, $containerClass);
            $transformer   = self::CONTAINERS_TRANSFORM[$containerClass];

            return $this->createMiddlewareInstance(
                $transformer::transform($container, $configuration),
                $configuration
            );
        }

        throw new RuntimeException(\sprintf(
            'container "%s" is unsupported',
            $containerClass
        ));
    }
}
