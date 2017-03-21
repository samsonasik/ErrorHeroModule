<?php

namespace ErrorHeroModule\Handler;

use Interop\Container\ContainerInterface;
use RuntimeException;
use Zend\Console\Console;
use Zend\Console\Request as ConsoleRequest;
use Zend\Diactoros\ServerRequest;
use Zend\Mail\Message;
use Zend\Mail\Transport\TransportInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LoggingFactory
{
    /**
     * @param ContainerInterface|ServiceLocatorInterface
     *
     * @throws RuntimeException when mail config is enabled but mail-message config is not a service instance of Message
     * @throws RuntimeException when mail config is enabled but mail-transport config is not a service instance of TransportInterface
     *
     * @return Logging
     */
    public function __invoke($container)
    {
        if (!Console::isConsole()) {
            $serverUrl  = $container->get('ViewHelperManager')->get('ServerUrl')->__invoke();
            if ($container->has('Request')) {
                $request    = $container->get('Request');
                $requestUri = $request->getRequestUri();
            } else {
                $request    = new ServerRequest();
                $requestUri = $request->getUri()->getPath();
            }
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
