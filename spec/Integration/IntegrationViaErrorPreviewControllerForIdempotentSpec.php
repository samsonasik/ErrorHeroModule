<?php

namespace ErrorHeroModule\Spec;

use ErrorHeroModule;
use Zend\Console\Console;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Mvc\Application;

describe('Integration via ErrorPreviewController For Idempotent Spec', function () {

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
                    \realpath(__DIR__).'/../Fixture/config/autoload/error-hero-module.local.php',
                    \realpath(__DIR__).'/../Fixture/config/module.local.php',
                ],
            ],
        ]);

        return $application;

    });

    given('tableGateway', function () {

        $serviceManager = $this->application->getServiceManager();
        $db             = $serviceManager->get(AdapterInterface::class);

        return new TableGateway('log', $db, null, new ResultSet());

    });

    describe('/error-preview', function() {

        it('show error page', function() {

            $this->tableGateway->delete([]);
            $countBefore = \count($this->tableGateway->select());

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('http://example.com/error-preview');
            $request->setRequestUri('/error-preview');

            \ob_start();
            $this->application->run();
            $content = \ob_get_clean();

            expect($content)->toContain('<title>Error');
            expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');
            expect($this->application->getResponse()->getStatusCode())->toBe(500);

            $countAfter = \count($this->tableGateway->select());

            expect($countBefore)->toBe(0);
            expect($countAfter)->toBe(1);

        });

        it('show error page, idempotent for error exist check in DB', function() {

            $countBefore = \count($this->tableGateway->select());

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('http://example.com/error-preview');
            $request->setRequestUri('/error-preview');

            \ob_start();
            $this->application->run();
            $content = \ob_get_clean();

            expect($content)->toContain('<title>Error');
            expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');
            expect($this->application->getResponse()->getStatusCode())->toBe(500);

            $countAfter = \count($this->tableGateway->select());

            expect($countBefore)->toBe(1);
            expect($countAfter)->toBe(1);

        });

    });

    describe('/error-preview/error', function() {

        it('show error page', function() {

            $this->tableGateway->delete([]);
            $countBefore = \count($this->tableGateway->select());

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('http://example.com/error-preview/error');
            $request->setRequestUri('/error-preview/error');

            \ob_start();
            $this->application->run();
            $content = \ob_get_clean();

            expect($content)->toContain('<title>Error');
            expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');

            $countAfter = \count($this->tableGateway->select());

            expect($countBefore)->toBe(0);
            expect($countAfter)->toBe(1);

        });

        it('show error page, idempotent for error exist check in DB', function() {

            $countBefore = \count($this->tableGateway->select());

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('http://example.com/error-preview/error');
            $request->setRequestUri('/error-preview/error');

            \ob_start();
            $this->application->run();
            $content = \ob_get_clean();

            expect($content)->toContain('<title>Error');
            expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');

            $countAfter = \count($this->tableGateway->select());

            expect($countBefore)->toBe(1);
            expect($countAfter)->toBe(1);

        });

    });

});
