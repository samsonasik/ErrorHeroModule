<?php

namespace ErrorHeroModule\Listener;

class MvcFactory
{
    public function __invoke($container)
    {
        return new Mvc(
            $container->get('config')['error-hero-module']
        );
    }
}
