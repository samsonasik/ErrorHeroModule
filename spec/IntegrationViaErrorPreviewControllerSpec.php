<?php

namespace ErrorHeroModule\Spec;

use ErrorHeroModule;
use ErrorHeroModule\Controller\ErrorPreviewController;
use Kahlan\Plugin\Quit;
use Kahlan\QuitException;
use Zend\Console\Console;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Log;
use Zend\Mvc\Application;

describe('Integration via ErrorPreviewController', function () {

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
                    realpath(__DIR__).'/Fixture/autoload/{{,*.}global,{,*.}local}.php',
                ],
            ],
        ]);

        $events         = $application->getEventManager();
        $serviceManager = $application->getServiceManager();
        $serviceManager->get('SendResponseListener')
                       ->detach($events);

        $db  = $serviceManager->get('Zend\Db\Adapter\Adapter');
        $tableGateway = new TableGateway('log', $db, null, new HydratingResultSet());
        $tableGateway->delete([]);

        return $application;

    });

    describe('/error-preview', function() {

        it('show error page', function() {

            skipIf(PHP_MAJOR_VERSION < 7);

            Quit::disable();

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('/error-preview');

            ob_start();
            $closure = function () {
                $this->application->run();
            };
            expect($closure)->toThrow(new QuitException());
            $content = ob_get_clean();

            expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');

        });

        it('show error console message in console env', function() {

            Console::overrideIsConsole(true);

            skipIf(PHP_MAJOR_VERSION < 7);

            Quit::disable();

            $application = Application::init([
                'modules' => [
                    'Zend\Router',
                    'Zend\Db',
                    'ErrorHeroModule',
                ],
                'module_listener_options' => [
                    'config_glob_paths' => [
                        realpath(__DIR__).'/Fixture/autoload/{{,*.}global,{,*.}local}.php',
                    ],
                ],
            ]);

            $events         = $application->getEventManager();
            $serviceManager = $application->getServiceManager();
            $serviceManager->get('SendResponseListener')
                           ->detach($events);

            $request     = $application->getRequest();
            $request->setMethod('GET');
            $request->setUri('/error-preview');

            ob_start();
            $closure = function () use ($application) {
                $application->run();
            };
            expect($closure)->toThrow(new QuitException());
            $content = ob_get_clean();

            expect($content)->toContain('|We have encountered a problem and we can not fulfill your request');

        });

    });

    describe('/error-preview/error', function() {

        it('show error page', function() {

            skipIf(PHP_MAJOR_VERSION < 7);

            Quit::disable();

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('/error-preview/error');

            ob_start();
            $closure = function () {
                $this->application->run();
            };
            expect($closure)->toThrow(new QuitException());
            $content = ob_get_clean();

            expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');

        });
    });

});
