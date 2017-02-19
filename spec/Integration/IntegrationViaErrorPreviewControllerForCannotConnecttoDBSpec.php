<?php

namespace ErrorHeroModule\Spec;

use ErrorHeroModule;
use ErrorHeroModule\Controller\ErrorPreviewController;
use Kahlan\Plugin\Quit;
use Kahlan\QuitException;
use Zend\Console\Console;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Log;
use Zend\Mvc\Application;

describe('Integration via ErrorPreviewController for Cannot connect to DB', function () {

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
                    realpath(__DIR__).'/../Fixture/config/autoload-for-cannot-connect-to-db/{{,*.}global,{,*.}local}.php',
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

    describe('/error-preview', function() {

        it('show error page', function() {

            Quit::disable();

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('/error-preview');

            ob_start();
            $closure = function () {
                $this->application->run();
            };
            expect($closure)->toThrow(new QuitException('Exit statement occurred', -1));
            $content = ob_get_clean();

            expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');

        });

    });

    describe('/error-preview/error', function() {

        it('show error page', function() {

            Quit::disable();

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('/error-preview/error');

            ob_start();
            $closure = function () {
                $this->application->run();
            };
            expect($closure)->toThrow(new QuitException('Exit statement occurred', -1));
            $content = ob_get_clean();

            expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');

        });
    });

});
