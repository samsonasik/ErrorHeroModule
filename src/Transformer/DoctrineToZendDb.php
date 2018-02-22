<?php

declare(strict_types=1);

namespace ErrorHeroModule\Transformer;

use Doctrine\ORM\EntityManager;
use Zend\Db\Adapter\Adapter;
use Zend\ServiceManager\ServiceManager;

class DoctrineToZendDb
{
    public static function transform(ServiceManager $container, array $configuration) : ServiceManager
    {
        $entityManager          = $container->get(EntityManager::class);
        $doctrineDBALConnection = $entityManager->getConnection();

        $params        = $doctrineDBALConnection->getParams();
        $driverOptions = (isset($params['driverOptions'])) ? $params['driverOptions'] : [];

        $config = [
            'username'       => $doctrineDBALConnection->getUsername(),
            'password'       => $doctrineDBALConnection->getPassword(),
            'driver'         => $doctrineDBALConnection->getDriver()->getName(),
            'database'       => $doctrineDBALConnection->getDatabase(),
            'host'           => $doctrineDBALConnection->getHost(),
            'port'           => $doctrineDBALConnection->getPort(),
            'driver_options' => $driverOptions,
        ];


        $allowOverride = $container->getAllowOverride();
        $container->setAllowOverride(true);

        $adapterName = 'Zend\Db\Adapter\Adapter';
        $writers = $configuration['log']['ErrorHeroModuleLogger']['writers'];
        foreach ($writers as $key => $writer) {
            if ($writer['name'] === 'db') {
                $adapterName = $writer['options']['db'];
                break;
            }
        }

        $container->setService($adapterName, new Adapter($config));
        $container->setAllowOverride($allowOverride);

        return $container;
    }
}