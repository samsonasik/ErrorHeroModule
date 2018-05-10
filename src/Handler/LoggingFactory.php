<?php

declare(strict_types=1);

namespace ErrorHeroModule\Handler;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Zend\Console\Console;
use Zend\Console\Request as ConsoleRequest;
use Zend\Mail\Message;
use Zend\Mail\Transport\TransportInterface;

class LoggingFactory
{
    /**
     * @throws RuntimeException when mail config is enabled but mail-message config is not a service instance of Message
     * @throws RuntimeException when mail config is enabled but mail-transport config is not a service instance of TransportInterface
     */
    public function __invoke(ContainerInterface $container) : Logging
    {
        if (! Console::isConsole()) {
            if ($container->has('Request')) {
                $request    = $container->get('Request');
                $uri        = $request->getUri();
                $serverUrl  = $uri->getScheme() . '://' . $uri->getHost();
                $port       = $uri->getPort();
                if ($port !== 80) {
                    $serverUrl .= ':' . $port;
                }
                $requestUri = $request->getRequestUri();
            } else {
                $serverUrl  = '';
                $request    = null;
                $requestUri = '';
            }
        } else {
            $serverUrl  = \php_uname('n');
            $request    = new ConsoleRequest();
            $requestUri = ':'. \basename((string) \getcwd())  .' ' . \get_current_user() . '$ php ' . $request->getScriptName() . ' ' . $request->toString();
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
            if (! $mailMessageService instanceof Message) {
                throw new RuntimeException('You are enabling email log writer, your "mail-message" config must be instanceof '.Message::class);
            }

            $mailMessageTransport = $container->get($mailConfig['mail-transport']);
            if (! $mailMessageTransport instanceof TransportInterface) {
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
