<?php

namespace ErrorHeroModule\Listener;

use ErrorHeroModule\Handler\Logging;

class MvcFactory
{
    public function __invoke($container)
    {
        return new Mvc(
            $container->get('config')['error-hero-module'],
            $container->get(Logging::class),
            $container->get('ViewRenderer')
        );
    }
}
