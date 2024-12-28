<?php

declare(strict_types=1);

namespace ErrorHeroModule\Compat;

use Psr\Container\ContainerInterface;

class LoggerServiceFactory extends \Laminas\Log\LoggerServiceFactory
{
    /**
     * @{inheritDoc}
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config    = $container->get('config');
        $logConfig = $config['log'] ?? [];

        $this->processConfig($logConfig, $container);

        return new Logger($logConfig);
    }
}
