<?php

declare(strict_types=1);

namespace ErrorHeroModule\Compat;

use Psr\Container\ContainerInterface;

class LoggerServiceFactory extends \Laminas\Log\LoggerServiceFactory
{
    /**
     * @param string $requestedName
     * @param null|array<mixed> $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Logger
    {
        $config    = $container->get('config');
        $logConfig = $config['log'] ?? [];

        $this->processConfig($logConfig, $container);

        return new Logger($logConfig);
    }
}
