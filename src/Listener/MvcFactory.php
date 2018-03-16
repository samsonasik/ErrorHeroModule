<?php

declare(strict_types=1);

namespace ErrorHeroModule\Listener;

use ErrorHeroModule\Handler\Logging;
use Psr\Container\ContainerInterface;

class MvcFactory
{
    public function __invoke(ContainerInterface $container) : Mvc
    {
        $config = $container->get('config');

        return new Mvc(
            $config['error-hero-module'],
            $container->get(Logging::class),
            $container->get('ViewRenderer')
        );
    }
}
