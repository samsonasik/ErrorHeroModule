<?php

declare(strict_types=1);

namespace ErrorHeroModule\Handler;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class LoggingFactory
{
    public function __invoke(ContainerInterface $container): Logging
    {
        /** @var array $config */
        $config = $container->get('config');
        /** @var LoggerInterface $errorHeroModuleLogger */
        $errorHeroModuleLogger = $container->get('ErrorHeroModuleLogger');

        $errorHeroModuleLocalConfig = $config['error-hero-module'];
        $mailConfig                 = $errorHeroModuleLocalConfig['email-notification-settings'];
        $includeFilesToAttachments  = $mailConfig['include-files-to-attachments'] ?? true;

        return new Logging(
            $errorHeroModuleLogger,
            $includeFilesToAttachments,
        );
    }
}
