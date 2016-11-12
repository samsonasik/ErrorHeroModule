<?php

namespace ErrorHeroModule\Spec\Integration;

use ErrorHeroModule;
use ErrorHeroModule\Controller\ErrorPreviewController;
use Kahlan\Plugin\Quit;
use Kahlan\QuitException;
use Zend\Console\Console;
use Zend\Mvc\Application;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\Log;

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
            ob_get_clean();

        });

        it('show error page, idempotent for error exist check in DB', function() {

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
            ob_get_clean();

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
            ob_get_clean();

        });

        it('show error page, idempotent for error exist check in DB', function() {

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
            ob_get_clean();

        });

    });

});
