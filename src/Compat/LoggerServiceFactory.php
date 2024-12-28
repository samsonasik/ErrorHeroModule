<?php

declare(strict_types=1);

namespace ErrorHeroModule\Compat;

use Psr\Container\ContainerInterface;

class LoggerServiceFactory extends \Laminas\Log\LoggerServiceFactory
{
    /**
     * @param string $requestedName
     * @param null|array $options
     * @return Logger
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config    = $container->get('config');
        $logConfig = $config['log'] ?? [];

        $this->processConfig($logConfig, $container);

        return new Logger($logConfig);
    }
}
