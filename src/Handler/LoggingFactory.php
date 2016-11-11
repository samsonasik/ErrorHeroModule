<?php

namespace ErrorHeroModule\Handler;

use Zend\Console\Console;

class LoggingFactory
{
    public function __invoke($container)
    {
        $serverUrl  = '';
        $request    = '';
        $requestUri = '';

        if (! Console::isConsole()) {
            $serverUrl  =  $container->get('ViewHelperManager')->get('ServerUrl')->__invoke();
            $request    =  $container->get('Request');
            $requestUri =  $request->getRequestUri();
        }

        $config                     = $container->get('config');
        $errorHeroModuleLocalConfig = $config['error-hero-module'];
        $logWritersConfig           = $config['log']['ErrorHeroModuleLogger']['writers'];

        $mailConfig           = $errorHeroModuleLocalConfig['email-notification-settings'];
        $mailMessageService   = null;
        $mailMessageTransport = null;
        if ($mailConfig['enable'] === true) {
            $mailMessageService   = $container->get($mailConfig['mail-message']);
            $mailMessageTransport = $container->get($mailConfig['mail-transport']);
        }

        return new Logging(
            $container->get('ErrorHeroModuleLogger'),
            $serverUrl,
            $request,
            $requestUri,
            $errorHeroModuleLocalConfig,
            $logWritersConfig,
            $mailMessageService,
            $mailMessageTransport
        );
    }
}
