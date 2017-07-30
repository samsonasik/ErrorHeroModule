<?php

namespace ErrorHeroModule;

use Doctrine\ORM\EntityManager;
use Zend\Db\Adapter\Adapter;
use Zend\ModuleManager\ModuleEvent;
use Zend\ModuleManager\ModuleManager;

class Module
{
    /**
     * @param  ModuleManager $moduleManager
     *
     * @return void
     */
    public function init(ModuleManager $moduleManager)
    {
        $eventManager = $moduleManager->getEventManager();
        $eventManager->attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'convertDoctrineToZendDbService']);
        $eventManager->attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'errorPreviewPageHandler'], 101);
    }

    /**
     * @param  ModuleEvent                   $event
     *
     * @return void
     */
    public function convertDoctrineToZendDbService(ModuleEvent $event)
    {
        $services       = $event->getParam('ServiceManager');
        if (! $services->has(EntityManager::class)) {
            return;
        }

        /** @var $configListener \Zend\ModuleManager\Listener\ConfigListener */
        $configListener = $event->getConfigListener();
        $configuration  = $configListener->getMergedConfig(false);

        if (isset($configuration['db'])) {
            return;
        }

        $entityManager          = $services->get(EntityManager::class);
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

        $allowOverride = $services->getAllowOverride();
        $services->setAllowOverride(true);

        $adapterName = 'Zend\Db\Adapter\Adapter';
        $writers = $configuration['log']['ErrorHeroModuleLogger']['writers'];
        foreach ($writers as $key => $writer) {
            if ($writer['name'] === 'db') {
                $adapterName = $writer['options']['db'];
                break;
            }
        }

        $services->setService($adapterName, new Adapter($config));
        $services->setAllowOverride($allowOverride);
    }

    /**
     * @param  ModuleEvent                   $event
     *
     * @return void
     */
    public function errorPreviewPageHandler(ModuleEvent $event)
    {
        $services       = $event->getParam('ServiceManager');
        /** @var $configListener \Zend\ModuleManager\Listener\ConfigListener */
        $configListener = $event->getConfigListener();
        $configuration  = $configListener->getMergedConfig(false);

        if (! isset($configuration['error-hero-module']['enable-error-preview-page'])) {
            return;
        }

        if ($configuration['error-hero-module']['enable-error-preview-page']) {
            return;
        }

        unset(
            $configuration['controllers']['invokables'][Controller\ErrorPreviewController::class],
            $configuration['controllers']['invokables'][Controller\ErrorPreviewConsoleController::class],
            $configuration['controllers']['factories'][Controller\ErrorPreviewController::class],
            $configuration['controllers']['factories'][Controller\ErrorPreviewConsoleController::class],
            $configuration['router']['routes']['error-preview'],
            $configuration['console']['router']['routes']['error-preview-console']
        );

        $configListener->setMergedConfig($configuration);
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__.'/../config/module.config.php';
    }
}
