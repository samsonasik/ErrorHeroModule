<?php

declare(strict_types=1);

namespace ErrorHeroModule;

use Doctrine\ORM\EntityManager;
use ErrorHeroModule\Command\Preview\ErrorPreviewConsoleCommand;
use ErrorHeroModule\Compat\Logger;
use ErrorHeroModule\Controller\ErrorPreviewController;
use ErrorHeroModule\Transformer\Doctrine;
use Laminas\ModuleManager\Listener\ConfigListener;
use Laminas\ModuleManager\ModuleEvent;
use Laminas\ModuleManager\ModuleManager;
use Laminas\ServiceManager\ServiceManager;

final class Module
{
    public function init(ModuleManager $moduleManager): void
    {
        $eventManager = $moduleManager->getEventManager();
        $eventManager->attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'doctrineTransform']);
        $eventManager->attach(ModuleEvent::EVENT_MERGE_CONFIG, [$this, 'setupCompatibleLogger'], 100);
        $eventManager->attach(ModuleEvent::EVENT_MERGE_CONFIG, [$this, 'errorPreviewPageHandler'], 101);
    }

    public function doctrineTransform(ModuleEvent $moduleEvent): void
    {
        /** @var ServiceManager $container */
        $container        = $moduleEvent->getParam('ServiceManager');
        $hasEntityManager = $container->has(EntityManager::class);

        if (! $hasEntityManager) {
            return;
        }

        /** @var array<string, mixed> $configuration */
        $configuration = $container->get('config');
        $configuration['db'] ?? Doctrine::transform($container, $configuration);
    }

    public function setupCompatibleLogger(ModuleEvent $moduleEvent): void
    {
        /** @var ServiceManager $container */
        $container     = $moduleEvent->getParam('ServiceManager');
        $configuration = $container->get('config');

        $container->setService('ErrorHeroModuleLogger', new Logger($configuration['log']['ErrorHeroModuleLogger']));
    }

    public function errorPreviewPageHandler(ModuleEvent $moduleEvent): void
    {
        /** @var ConfigListener $configMerger */
        $configMerger = $moduleEvent->getConfigListener();
        /** @var array $configuration */
        $configuration = $configMerger->getMergedConfig(false);

        if (! isset($configuration['error-hero-module']['enable-error-preview-page'])) {
            return;
        }

        if ($configuration['error-hero-module']['enable-error-preview-page']) {
            return;
        }

        unset(
            $configuration['controllers']['factories'][ErrorPreviewController::class],
            $configuration['service_manager']['factories'][ErrorPreviewConsoleCommand::class],
            $configuration['router']['routes']['error-preview'],
            $configuration['laminas-cli']['commands']['errorheromodule:preview']
        );

        $configMerger->setMergedConfig($configuration);
    }

    /**
     * @return mixed[]
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}
