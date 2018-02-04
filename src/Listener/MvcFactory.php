<?php

namespace ErrorHeroModule\Listener;

use ErrorHeroModule\Handler\Logging;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class MvcFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) : Mvc
    {
        $config = $container->get('config');

        return new Mvc(
            $config['error-hero-module'],
            $container->get(Logging::class),
            $container->get('ViewRenderer')
        );
    }
}
