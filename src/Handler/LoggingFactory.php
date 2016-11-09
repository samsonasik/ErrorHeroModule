<?php

namespace ErrorHeroModule\Handler;

class LoggingFactory
{
    public function __invoke($container)
    {
        return new Logging(
            $container->get('ErrorHeroModuleLogger')
        );
    }
}
