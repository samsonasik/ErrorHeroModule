<?php

namespace ErrorHeroModule;

use Zend\Mvc\MvcEvent;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $app           = $e->getApplication();
        $services      = $app->getServiceManager();

        $eventManager  = $app->getEventManager();
        $eventManager->attach($services->get(Listener\Mvc::class));
    }

    public function getConfig()
    {
        return include __DIR__.'/../config/module.config.php';
    }
}
