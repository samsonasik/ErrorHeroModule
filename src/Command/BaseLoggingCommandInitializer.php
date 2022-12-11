<?php

declare(strict_types=1);

namespace ErrorHeroModule\Command;

use ErrorHeroModule\Handler\Logging;
use Laminas\ServiceManager\Initializer\InitializerInterface;
use Psr\Container\ContainerInterface;

class BaseLoggingCommandInitializer implements InitializerInterface
{
    public function __invoke(ContainerInterface $container, $instance)
    {
        if (! $instance instanceof BaseLoggingCommand) {
            return;
        }

        $instance->init(
            $container->get('config')['error-hero-module'],
            $container->get(Logging::class)
        );
    }
}