<?php

namespace ErrorHeroModule\Spec\Integration;

use ErrorHeroModule;
use ErrorHeroModule\Controller\ErrorPreviewController;
use Zend\Console\Console;
use Zend\Mvc\Application;

describe('Integration via ErrorPreviewController for error shown', function () {

    given('application', function () {

        Console::overrideIsConsole(false);

        $modules_add = [];
        if (class_exists(\Zend\Router\Module::class)) {
            $modules_add = [
                'Zend\Router',
                'Zend\Db'
            ];
        }

        $application = Application::init([
            'modules' => array_merge(
                $modules_add,
                [
                    'ErrorHeroModule',
                ]
            ),
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

        it('show error page', function() {

            skipIf(PHP_MAJOR_VERSION < 7);

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('/error-preview/error');

            try {
                ob_start();
                $this->application->run();
                $content = ob_get_clean();

                expect($content)->toBe('');
            } catch (\Throwable $t) {

            }
        });
    });

});
