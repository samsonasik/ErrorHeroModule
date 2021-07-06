<?php

namespace ErrorHeroModule\Spec;

use ErrorHeroModule;
use Kahlan\Plugin\Quit;
use Laminas\Console\Console;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Mvc\Application;

describe('Integration via ErrorPreviewController with enable send mail with empty receiver', function () {

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
                    \realpath(__DIR__).'/../Fixture/config/autoload-with-enable-sendmail-with-empty-email-receivers/error-hero-module.local.php',
                    \realpath(__DIR__).'/../Fixture/config/module.local.php',
                ],
            ],
        ]);

        $serviceManager = $application->getServiceManager();
        $db  = $serviceManager->get(AdapterInterface::class);
        $tableGateway = new TableGateway('log', $db, null, new ResultSet());
        $tableGateway->delete([]);

        return $application;

    });

    describe('/error-preview', function() {

        it('show error page for exception', function() {

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

        });

    });

    describe('/error-preview/error', function() {

        it('show error page for E_* error', function() {

            Quit::disable();

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('http://example.com/error-preview/error');
            $request->setRequestUri('/error-preview/error');

            \ob_start();
            $this->application->run();
            $content = \ob_get_clean();

            expect($content)->toContain('<title>Error');
            expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');
            expect($this->application->getResponse()->getStatusCode())->toBe(500);

        });

    });

});
