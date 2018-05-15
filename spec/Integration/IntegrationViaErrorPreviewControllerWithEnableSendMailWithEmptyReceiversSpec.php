<?php

namespace ErrorHeroModule\Spec;

use ErrorHeroModule;
use ErrorHeroModule\Controller\ErrorPreviewController;
use Kahlan\Plugin\Quit;
use Zend\Console\Console;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Mvc\Application;

describe('Integration via ErrorPreviewController with enable send mail with empty receiver', function () {

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
                    realpath(__DIR__).'/../Fixture/config/autoload-with-enable-sendmail-with-empty-email-receivers/error-hero-module.local.php',
                    realpath(__DIR__).'/../Fixture/config/module.local.php',
                ],
            ],
        ]);

        $events         = $application->getEventManager();
        $serviceManager = $application->getServiceManager();

        $db  = $serviceManager->get(Adapter::class);
        $tableGateway = new TableGateway('log', $db, null, new ResultSet());
        $tableGateway->delete([]);

        return $application;

    });

    describe('/error-preview', function() {

        it('show error page for exception', function() {

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('/error-preview');

            ob_start();
            $this->application->run();
            $content = ob_get_clean();

            expect($content)->toContain('<title>Error');
            expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');

        });

    });

    describe('/error-preview/error', function() {

        it('show error page for E_* error', function() {

            Quit::disable();

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('/error-preview/error');

            ob_start();
            $this->application->run();
            $content = ob_get_clean();

            expect($content)->toContain('<title>Error');
            expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');

        });

    });

});
