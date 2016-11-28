<?php

namespace ErrorHeroModule\Middleware;

use ErrorHeroModule\Handler\Logging;

class ErrorActionFactory
{
    public function __invoke($container)
    {
        return new ErrorAction(
            $container->get('config')['error-hero-module'],
            $container->get(Logging::class),
            $container->get('ViewRenderer')
        );
    }
}
