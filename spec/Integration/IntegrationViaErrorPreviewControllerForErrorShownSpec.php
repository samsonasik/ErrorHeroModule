<?php

namespace ErrorHeroModule\Spec\Integration;

use ErrorHeroModule;
use ErrorHeroModule\Controller\ErrorPreviewController;
use Zend\Console\Console;
use Zend\Mvc\Application;

describe('Integration via ErrorPreviewController for error shown', function () {

    given('application', function () {

        Console::overrideIsConsole(false);

        $application = Application::init([
            'modules' => [
                'Zend\Router',
                'Zend\Db',
                'ErrorHeroModule',
            ],
            'module_listener_options' => [
                'config_glob_paths' => [
                    realpath(__DIR__).'/../Fixture/config/autoload-for-enable-display-errors/error-hero-module.local.php',
                    realpath(__DIR__).'/../Fixture/config/module.local.php',
                ],
            ],
        ]);

        $events         = $application->getEventManager();
        $serviceManager = $application->getServiceManager();
        $serviceManager->get('SendResponseListener')
                       ->detach($events);

        return $application;

    });

    describe('/error-preview/error', function() {

        it('empty as rely to original mvc process to handle', function() {

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('/error-preview/error');

            ob_start();
            $this->application->run();
            expect(ob_get_clean())->toBe('');

        });
    });

});
