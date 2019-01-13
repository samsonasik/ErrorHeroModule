<?php

declare(strict_types=1);

namespace ErrorHeroModule\Transformer;

use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;
use Zend\ServiceManager\ServiceManager as ZendServiceManager;

class Doctrine extends TransformerAbstract implements TransformerInterface
{
    public static function transform(ContainerInterface $container, array $configuration) : ContainerInterface
    {
        Assert::isInstanceOf($container, ZendServiceManager::class);

        $entityManager          = $container->get(EntityManager::class);
        $doctrineDBALConnection = $entityManager->getConnection();

        $params        = $doctrineDBALConnection->getParams();
        $driverOptions = $params['driverOptions'] ?? [];

        $dbAdapterConfig = [
            'username'       => $doctrineDBALConnection->getUsername(),
            'password'       => $doctrineDBALConnection->getPassword(),
            'driver'         => $doctrineDBALConnection->getDriver()->getName(),
            'database'       => $doctrineDBALConnection->getDatabase(),
            'host'           => $doctrineDBALConnection->getHost(),
            'port'           => $doctrineDBALConnection->getPort(),
            'driver_options' => $driverOptions,
        ];

        $logger   = parent::getLoggerInstance($configuration, $dbAdapterConfig);

        return $container->configure([
            'services' => [
                'ErrorHeroModuleLogger' => $logger,
            ],
        ]);
    }
}