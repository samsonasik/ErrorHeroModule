<?php

namespace ErrorHeroModule\Middleware;

use ErrorHeroModule\Handler\Logging;

class ExceptionActionFactory
{
    public function __invoke($container)
    {
        return new ExceptionAction(
            $container->get('config')['error-hero-module'],
            $container->get(Logging::class),
            $container->get('ViewRenderer')
        );
    }
}
