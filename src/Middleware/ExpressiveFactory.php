<?php

namespace ErrorHeroModule\Middleware;

use ErrorHeroModule\Handler\Logging;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ExpressiveFactory
{
    /**
     * @param ContainerInterface|ServiceLocatorInterface
     *
     * @return Expressive
     */
    public function __invoke($container)
    {
        $config = $container->get('config');

        return new Expressive(
            $config['error-hero-module'],
            $container->get(Logging::class),
            $container->get('ViewRenderer')
        );
    }
}
