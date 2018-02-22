<?php

declare(strict_types=1);

namespace ErrorHeroModule;

use Doctrine\ORM\EntityManager;
use Zend\ModuleManager\ModuleEvent;
use Zend\ModuleManager\ModuleManager;

class Module
{
    /**
     * @param  ModuleManager $moduleManager
     *
     * @return void
     */
    public function init(ModuleManager $moduleManager) : void
    {
        $eventManager = $moduleManager->getEventManager();
        $eventManager->attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'convertDoctrineToZendDbService']);
        $eventManager->attach(ModuleEvent::EVENT_MERGE_CONFIG, [$this, 'errorPreviewPageHandler'], 101);
    }

    public function convertDoctrineToZendDbService(ModuleEvent $event) : void
    {
        $container = $event->getParam('ServiceManager');
        if (! $container->has(EntityManager::class)) {
            return;
        }

        /** @var \Zend\ModuleManager\Listener\ConfigListener $configListener */
        $configListener = $event->getConfigListener();
        $configuration  = $configListener->getMergedConfig(false);

        if (isset($configuration['db'])) {
            return;
        }

        Transformer\DoctrineToZendDb::transform($container, $configuration);
    }

    public function errorPreviewPageHandler(ModuleEvent $event) : void
    {
        /** @var \Zend\ModuleManager\Listener\ConfigListener $configListener */
        $configListener = $event->getConfigListener();
        $configuration  = $configListener->getMergedConfig(false);

        if (! isset($configuration['error-hero-module']['enable-error-preview-page'])) {
            return;
        }

        if ($configuration['error-hero-module']['enable-error-preview-page']) {
            return;
        }

        unset(
            $configuration['controllers']['factories'][Controller\ErrorPreviewController::class],
            $configuration['controllers']['factories'][Controller\ErrorPreviewConsoleController::class],
            $configuration['router']['routes']['error-preview'],
            $configuration['console']['router']['routes']['error-preview-console']
        );

        $configListener->setMergedConfig($configuration);
    }

    public function getConfig() : array
    {
        return include __DIR__.'/../config/module.config.php';
    }
}
