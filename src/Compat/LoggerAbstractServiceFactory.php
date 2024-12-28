<?php

declare(strict_types=1);

namespace ErrorHeroModule\Compat;

use Psr\Container\ContainerInterface;

class LoggerAbstractServiceFactory extends \Laminas\Log\LoggerAbstractServiceFactory
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $this->getConfig($container);
        $config = $config[$requestedName];

        $this->processConfig($config, $container);

        return new Logger($config);
    }
}
