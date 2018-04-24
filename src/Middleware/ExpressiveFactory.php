<?php

declare(strict_types=1);

namespace ErrorHeroModule\Middleware;

use Doctrine\ORM\EntityManager;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Transformer\DoctrineToZendDb;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container as SymfonyContainer;
use Zend\Expressive\Template\TemplateRendererInterface;

class ExpressiveFactory
{
    public function __invoke(ContainerInterface $container) : Expressive
    {
        $configuration = $container->get('config');

        if ($container->has(EntityManager::class) && ! isset($configuration['db'])) {
            $container = DoctrineToZendDb::transform($container, $configuration);
        }

        if (isset($configuration['db']) && $container instanceof SymfonyContainer) {
            // ...
        }

        return new Expressive(
            $configuration['error-hero-module'],
            $container->get(Logging::class),
            $container->get(TemplateRendererInterface::class)
        );
    }
}
