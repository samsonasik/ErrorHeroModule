<?php

namespace ErrorHeroModule\Listener;

use ErrorHeroModule\Handler\Logging;

class MvcFactory
{
    public function __invoke($container)
    {
        $config = $container->get('config');
        
        return new Mvc(
            $config['error-hero-module'],
            $container->get(Logging::class)
        );
    }
}
