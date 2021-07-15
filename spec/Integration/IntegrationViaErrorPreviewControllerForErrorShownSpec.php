<?php

namespace ErrorHeroModule\Spec\Integration;

use Laminas\Console\Console;
use Laminas\Mvc\Application;

describe('Integration via ErrorPreviewController for error shown', function () {

    given('application', function () {

        Console::overrideIsConsole(false);

        $application = Application::init([
            'modules' => [
                'Laminas\Router',
                'Laminas\Db',
                'ErrorHeroModule',
            ],
            'module_listener_options' => [
                'config_glob_paths' => [
                    \realpath(__DIR__).'/../Fixture/config/autoload-for-enable-display-errors/error-hero-module.local.php',
                    \realpath(__DIR__).'/../Fixture/config/module.local.php',
                ],
            ],
        ]);

        $eventManager         = $application->getEventManager();
        $serviceManager = $application->getServiceManager();
        $serviceManager->get('SendResponseListener')
                       ->detach($eventManager);

        return $application;

    });

    describe('/error-preview/error', function(): void {

        it('empty as rely to original mvc process to handle', function(): void {

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('http://example.com/error-preview/error');
            $request->setRequestUri('/error-preview/error');

            \ob_start();
            $this->application->run();
            expect(\ob_get_clean())->toBe('');
            expect($this->application->getResponse()->getStatusCode())->toBe(500);

        });
    });

});
