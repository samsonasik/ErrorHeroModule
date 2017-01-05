<?php

namespace ErrorHeroModule\Handler;

use RuntimeException;
use Zend\Console\Console;
use Zend\Console\Request as ConsoleRequest;
use Zend\Mail\Message;
use Zend\Mail\Transport\TransportInterface;

class LoggingFactory
{
    public function __invoke($container)
    {
        if (!Console::isConsole()) {
            $serverUrl  = $container->get('ViewHelperManager')->get('ServerUrl')->__invoke();
            $request    = $container->get('Request');
            $requestUri = $request->getRequestUri();
        } else {
            $serverUrl  = php_uname('n');
            $request    = new ConsoleRequest();
            $requestUri = ':'. basename(getcwd())  .' ' . get_current_user() . '$ php ' . $request->getScriptName() . ' ' . $request->toString();
        }

        $config                = $container->get('config');
        $errorHeroModuleLogger = $container->get('ErrorHeroModuleLogger');

        $errorHeroModuleLocalConfig = $config['error-hero-module'];
        $logWritersConfig           = $config['log']['ErrorHeroModuleLogger']['writers'];

        $mailConfig           = $errorHeroModuleLocalConfig['email-notification-settings'];
        $mailMessageService   = null;
        $mailMessageTransport = null;

        if ($mailConfig['enable'] === true) {
            $mailMessageService   = $container->get($mailConfig['mail-message']);
            if (!$mailMessageService instanceof Message) {
                throw new RuntimeException('You are enabling email log writer, your "mail-message" config must be instanceof '.Message::class);
            }

            $mailMessageTransport = $container->get($mailConfig['mail-transport']);
            if (!$mailMessageTransport instanceof TransportInterface) {
                throw new RuntimeException('You are enabling email log writer, your "mail-transport" config must implements '.TransportInterface::class);
            }
        }

        return new Logging(
            $errorHeroModuleLogger,
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
