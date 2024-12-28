<?php

declare(strict_types=1);

namespace ErrorHeroModule\Handler;

use ErrorHeroModule\Compat\Logger;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function sprintf;

final class LoggingFactory
{
    /**
     * @throws RuntimeException When mail config is enabled
     * but mail-message and/or mail-transport config is not a service instance of Message.
     */
    public function __invoke(ContainerInterface $container): Logging
    {
        /** @var array $config */
        $config = $container->get('config');
        /** @var Logger $errorHeroModuleLogger */
        $errorHeroModuleLogger = new Logger($config['log']['ErrorHeroModuleLogger']);

        $errorHeroModuleLocalConfig = $config['error-hero-module'];
        $logWritersConfig           = $config['log']['ErrorHeroModuleLogger']['writers'];

        $mailConfig           = $errorHeroModuleLocalConfig['email-notification-settings'];
        $mailMessageService   = null;
        $mailMessageTransport = null;

        if ($mailConfig['enable'] === true) {
            $mailMessageService = $container->get($mailConfig['mail-message']);
            if (! $mailMessageService instanceof Message) {
                throw new RuntimeException(sprintf(
                    'You are enabling email log writer, your "mail-message" config must be instanceof %s',
                    Message::class
                ));
            }

            $mailMessageTransport = $container->get($mailConfig['mail-transport']);
            if (! $mailMessageTransport instanceof TransportInterface) {
                throw new RuntimeException(sprintf(
                    'You are enabling email log writer, your "mail-transport" config must implements %s',
                    TransportInterface::class
                ));
            }
        }

        $includeFilesToAttachments = $mailConfig['include-files-to-attachments'] ?? true;

        return new Logging(
            $errorHeroModuleLogger,
            $errorHeroModuleLocalConfig,
            $logWritersConfig,
            $mailMessageService,
            $mailMessageTransport,
            $includeFilesToAttachments
        );
    }
}
