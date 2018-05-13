<?php

declare(strict_types=1);

namespace ErrorHeroModule\Transformer;

use Assert\Assertion;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Zend\Db\Adapter\Adapter;
use Zend\Log\Logger;
use Zend\ServiceManager\ServiceManager as ZendServiceManager;

class Doctrine implements TransformerInterface
{
    public static function transform(ContainerInterface $container, array $configuration) : ContainerInterface
    {
        Assertion::isInstanceOf($container, ZendServiceManager::class);

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

        $writers = $configuration['log']['ErrorHeroModuleLogger']['writers'];
        foreach ($writers as $key => $writer) {
            if ($writer['name'] === 'db') {
                $writers[$key]['options']['db'] = new Adapter($config);
                break;
            }
        }

        return $container->configure([
            'services' => [
                'ErrorHeroModuleLogger' => new Logger(['writers' => $writers]),
            ],
        ]);
    }
}