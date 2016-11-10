<?php

namespace ErrorHeroModule\Spec;

use ErrorHeroModule\Listener\Mvc;
use ErrorHeroModule\Module;
use Kahlan\Plugin\Double;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

describe('Module', function () {

    given('module', function() {
        return new Module();
    });

    describe('->getConfig()', function () {

        it('return "config" array', function () {

            $moduleConfig = include __DIR__.'/../config/module.config.php';

            $actual = $this->module->getConfig();
            expect($actual)->toBe($moduleConfig);

        });

    });

    describe('->onBootstrap()', function () {

        it('pull Mvc Listener and use it to attach with EventManager', function () {

            $application     = Double::instance(['extends' => Application::class, 'methods' => '__construct']);
            $mvcListener     = Double::instance(['extends' => Mvc::class, 'methods' => '__construct']);
            $serviceManager  = Double::instance(['implements' => ServiceLocatorInterface::class]);
            $eventManager    = Double::instance(['implements' => EventManagerInterface::class]);
            $mvcEvent        = Double::instance(['extends' => MvcEvent::class]);

            expect($mvcListener)->toReceive('attach')->with($eventManager);

            allow($application)->toReceive('getServiceManager')->andReturn($serviceManager);
            allow($application)->toReceive('getEventManager')->andReturn($eventManager);
            allow($serviceManager)->toReceive('get')->with(Mvc::class)->andReturn($mvcListener);
            allow($mvcEvent)->toReceive('getApplication')->andReturn($application);

            $this->module->onBootstrap($mvcEvent);

        });

    });

});
