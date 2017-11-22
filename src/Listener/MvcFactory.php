<?php

namespace ErrorHeroModule\Listener;

use ErrorHeroModule\Handler\Logging;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MvcFactory
{
    /**
     * @param ContainerInterface|ServiceLocatorInterface $container
     *
     * @return Mvc
     */
    public function __invoke($container)
    {
        $config = $container->get('config');

        return new Mvc(
            $config['error-hero-module'],
            $container->get(Logging::class),
            $container->get('ViewRenderer')
        );
    }
}
