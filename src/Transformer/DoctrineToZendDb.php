<?php

declare(strict_types=1);

namespace ErrorHeroModule\Transformer;

use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Zend\Db\Adapter\Adapter;
use Zend\ServiceManager\ServiceManager;

class DoctrineToZendDb implements TransformerInterface
{
    public static function transform(ContainerInterface $container, array $configuration) : ContainerInterface
    {
        if ($container instanceof ServiceManager) {
            $entityManager          = $container->get(EntityManager::class);
            $doctrineDBALConnection = $entityManager->getConnection();

            $params        = $doctrineDBALConnection->getParams();
            $driverOptions = $params['driverOptions'] ?? [];

            $config = [
                'username'       => $doctrineDBALConnection->getUsername(),
                'password'       => $doctrineDBALConnection->getPassword(),
                'driver'         => $doctrineDBALConnection->getDriver()->getName(),
                'database'       => $doctrineDBALConnection->getDatabase(),
                'host'           => $doctrineDBALConnection->getHost(),
                'port'           => $doctrineDBALConnection->getPort(),
                'driver_options' => $driverOptions,
            ];

            $adapterName = Adapter::class;
            $writers = $configuration['log']['ErrorHeroModuleLogger']['writers'];
            foreach ($writers as $key => $writer) {
                if ($writer['name'] === 'db') {
                    $adapterName = $writer['options']['db'];
                    break;
                }
            }

            $allowOverride = $container->getAllowOverride();
            $container->setAllowOverride(true);
            $container->setService($adapterName, new Adapter($config));
            $container->setAllowOverride($allowOverride);
        }

        return $container;
    }
}