<?php

declare(strict_types=1);

namespace ErrorHeroModule\Command;

use ErrorHeroModule\Handler\Logging;
use Laminas\ServiceManager\Initializer\InitializerInterface;
use Psr\Container\ContainerInterface;

final class BaseLoggingCommandInitializer implements InitializerInterface
{
    public function __invoke(ContainerInterface $container, mixed $instance): void
    {
        if (! $instance instanceof BaseLoggingCommand) {
            return;
        }

        /** @var array $config */
        $config = $container->get('config');

        $errorHeroModuleConfig = $config['error-hero-module'];

        /** @var Logging $logging */
        $logging = $container->get(Logging::class);

        $instance->init($errorHeroModuleConfig, $logging);
    }
}
