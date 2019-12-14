<?php

namespace ErrorHeroModule\Spec;

use ErrorHeroModule;
use Zend\Console\Console;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
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
                    \realpath(__DIR__).'/../Fixture/config/autoload-for-specific-error-and-exception/error-hero-module.local.php',
                    \realpath(__DIR__).'/../Fixture/config/module.local.php',
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

        it('empty as rely to original mvc process to handle', function() {

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('http://example.com/error-preview');
            $request->setRequestUri('/error-preview');

            \ob_start();
            $this->application->run();
            $content = \ob_get_clean();

            expect(\ob_get_clean())->toBe('');
            expect($this->application->getResponse()->getStatusCode())->toBe(500);

           // $select = $this->tableGateway->getSql()->select();
           // echo $select->count();

        });

    });

    describe('/error-preview/notice', function() {

        it('empty as rely to original mvc process to handle', function() {

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('http://example.com/error-preview/notice');
            $request->setRequestUri('/error-preview/notice');

            \ob_start();
            $this->application->run();
            $content = \ob_get_clean();

            expect(\ob_get_clean())->toBe('');
            $this->application->getResponse()->setStatusCode(http_response_code());
            expect($this->application->getResponse()->getStatusCode())->toBe(500);


        });

    });

    describe('/error-preview/error', function() {

        it('empty as rely to original mvc process to handle', function() {

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('http://example.com/error-preview/error');
            $request->setRequestUri('/error-preview/error');

            \ob_start();
            $this->application->run();
            $content = \ob_get_clean();

            expect(\ob_get_clean())->toBe('');
            expect($this->application->getResponse()->getStatusCode())->toBe(500);


        });

    });

});
