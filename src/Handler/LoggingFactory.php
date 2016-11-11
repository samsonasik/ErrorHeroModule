<?php

namespace ErrorHeroModule\Handler;

use Zend\Console\Console;

class LoggingFactory
{
    public function __invoke($container)
    {
        $serverUrl  = '';
        $requestUri = '';

        if (! Console::isConsole()) {
            $serverUrl  =  $container->get('ViewHelperManager')->get('ServerUrl')->__invoke();
            $requestUri =  $container->get('Request')->getRequestUri();
        }

        $config                = $container->get('config');
        $configLoggingSettings = $config['error-hero-module']['logging-settings'];
        $logWritersConfig      = $config['log']['ErrorHeroModuleLogger']['writers'];

        return new Logging(
            $container->get('ErrorHeroModuleLogger'),
            $serverUrl,
            $requestUri,
            $configLoggingSettings,
            $logWritersConfig
        );
    }
}
