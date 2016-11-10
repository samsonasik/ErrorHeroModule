<?php

namespace ErrorHeroModule\Handler;

use Zend\Console\Console;

class LoggingFactory
{
    public function __invoke($container)
    {
        $requestUri = '';
        if (! Console::isConsole()) {
            $requestUri =  $container->get('Request')->getRequestUri();
        }

        return new Logging(
            $container->get('ErrorHeroModuleLogger'),
            $requestUri
        );
    }
}
