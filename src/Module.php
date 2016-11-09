<?php

namespace ErrorHeroModule;

use Zend\Mvc\MvcEvent;
use AcMailer\Service\MailService;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $app           = $e->getApplication();
        $services      = $app->getServiceManager();

        if ($services->has(MailService::class)) {
            
        }

        $eventManager  = $app->getEventManager();
        $eventManager->attach($services->get(Listener\Mvc::class));
    }

    public function getConfig()
    {
        return include __DIR__.'/../config/module.config.php';
    }
}
