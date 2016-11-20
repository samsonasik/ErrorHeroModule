<?php

namespace ErrorHeroModule;

use Doctrine\ORM\EntityManager;
use Zend\ModuleManager\ModuleEvent;
use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\MvcEvent;

class Module
{
    /**
     * @param  ModuleManager $moduleManager
     * @return void
     */
    public function init(ModuleManager $moduleManager)
    {
        $eventManager = $moduleManager->getEventManager();
        $eventManager->attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'convertDoctrineToZendDbConfig']);
    }

    /**
     * @param  ModuleEvent                   $event
     * @return void
     */
    public function convertDoctrineToZendDbConfig(ModuleEvent $event)
    {
        $services       = $event->getParam('ServiceManager');
        if (! $services->has(EntityManager::class)) {
            return;
        }

        $configListener = $event->getConfigListener();
        $configuration  = $configListener->getMergedConfig(false);

        if (isset($configuration['db'])) {
            return;
        }

        $entityManager          = $services->get(EntityManager::class);
        $doctrineDBALConnection = $entityManager->getConnection();

        $params        = $doctrineDBALConnection->getParams();
        $driverOptions = (isset($params['driverOptions'])) ? $params['driverOptions'] : [];

        $configuration['db'] = [
            'username'       => $doctrineDBALConnection->getUsername(),
            'password'       => $doctrineDBALConnection->getPassword(),
            'driver'         => $doctrineDBALConnection->getDriver()->getName(),
            'database'       => $doctrineDBALConnection->getDatabase(),
            'host'           => $doctrineDBALConnection->getHost(),
            'port'           => $doctrineDBALConnection->getPort(),
            'driver_options' => $driverOptions,
        ];

        $configuration['service_manager']['factories']['Zend\Db\Adapter\Adapter'] = 'Zend\Db\Adapter\AdapterServiceFactory';

        $configListener->setMergedConfig($configuration);
        $event->setConfigListener($configListener);

        $allowOverride = $services->getAllowOverride();
        $services->setAllowOverride(true);
        $services->setService('config', $configuration);
        $services->setAllowOverride($allowOverride);
    }

    public function onBootstrap(MvcEvent $e)
    {
        $app = $e->getApplication();
        $services = $app->getServiceManager();
        $eventManager = $app->getEventManager();

        $mvcListenerAggregate = $services->get(Listener\Mvc::class);
        $mvcListenerAggregate->attach($eventManager);
    }

    public function getConfig()
    {
        return include __DIR__.'/../config/module.config.php';
    }
}
