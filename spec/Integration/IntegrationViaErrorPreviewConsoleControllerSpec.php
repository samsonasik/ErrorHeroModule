<?php

namespace ErrorHeroModule\Spec;

use ErrorHeroModule;
use ErrorHeroModule\Controller\ErrorPreviewConsoleController;
use Kahlan\Plugin\Quit;
use Kahlan\QuitException;
use Zend\Console\Console;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Mvc\Application;

describe('Integration via ErrorPreviewConsoleController', function () {

    given('application', function () {

        Console::overrideIsConsole(true);

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
                    realpath(__DIR__).'/../Fixture/config/autoload/error-hero-module.local.php',
                    realpath(__DIR__).'/../Fixture/config/module.local.php',
                ],
            ],
        ]);

        $events         = $application->getEventManager();
        $serviceManager = $application->getServiceManager();
        $serviceManager->get('SendResponseListener')
                       ->detach($events);

        $db  = $serviceManager->get('Zend\Db\Adapter\Adapter');
        $tableGateway = new TableGateway('log', $db, null, new ResultSet());
        $tableGateway->delete([]);

        return $application;

    });

    describe('error-preview', function() {

        it('show error page', function() {

            Quit::disable();

            $_SERVER['argv'] = [
                __FILE__,
                'error-preview',
                'controller' => ErrorPreviewConsoleController::class,
                'action' => 'exception',
            ];

            ob_start();
            $closure = function () {
                $this->application->run();
            };
            expect($closure)->toThrow(new QuitException('Exit statement occurred', -1));
            $content = ob_get_clean();

            expect($content)->toContain('|We have encountered a problem and we can not fulfill your request');

        });

    });

    describe('error-preview error', function() {

        it('show error page', function() {

            Quit::disable();

            $_SERVER['argv'] = [
                __FILE__,
                'error-preview',
                'controller' => ErrorPreviewConsoleController::class,
                'action' => 'error',
            ];

            ob_start();
            $closure = function () {
                $this->application->run();
            };
            expect($closure)->toThrow(new QuitException('Exit statement occurred', -1));
            $content = ob_get_clean();

            expect($content)->toContain('|We have encountered a problem and we can not fulfill your request');

        });
    });

});
