<?php

declare(strict_types=1);

namespace ErrorHeroModule\Middleware;

use Doctrine\ORM\EntityManager;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Transformer\DoctrineToZendDb;
use ErrorHeroModule\Transformer\SymfonyService;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Zend\Expressive\Template\TemplateRendererInterface;

class ExpressiveFactory
{
    public function __invoke(ContainerInterface $container) : Expressive
    {
        $configuration = $container->get('config');

        if ($container->has(EntityManager::class) && ! isset($configuration['db'])) {
            $container = DoctrineToZendDb::transform($container, $configuration);
        }

        if ($container instanceof SymfonyContainerBuilder) {
            if (! isset($configuration['db'])) {
                throw new RuntimeException('db config is required for build Zend\Db\Adapter\Adapter instance by Symfony Container');
            }

            $container = SymfonyService::transform($container, $configuration);
        }

        return new Expressive(
            $configuration['error-hero-module'],
            $container->get(Logging::class),
            $container->get(TemplateRendererInterface::class)
        );
    }
}
