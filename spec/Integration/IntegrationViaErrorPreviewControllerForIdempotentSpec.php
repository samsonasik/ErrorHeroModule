<?php

namespace ErrorHeroModule\Spec\Integration;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Mvc\Application;

describe('Integration via ErrorPreviewController For Idempotent Spec', function (): void {

    given('application', function (): Application {

        return Application::init([
            'modules' => [
                'Laminas\Router',
                'Laminas\Db',
                'ErrorHeroModule',
            ],
            'module_listener_options' => [
                'config_glob_paths' => [
                    \realpath(__DIR__).'/../Fixture/config/autoload/error-hero-module.local.php',
                    \realpath(__DIR__).'/../Fixture/config/module.local.php',
                ],
            ],
        ]);

    });

    given('tableGateway', function (): TableGateway {

        $serviceManager = $this->application->getServiceManager();
        $db             = $serviceManager->get(AdapterInterface::class);

        return new TableGateway('log', $db, null, new ResultSet());

    });

    describe('/error-preview', function(): void {

        it('show error page', function(): void {

            $this->tableGateway->delete([]);
            $countBefore = $this->tableGateway->select()->count();

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

            $countAfter = $this->tableGateway->select()->count();

            expect($countBefore)->toBe(0);
            expect($countAfter)->toBe(1);

        });

        it('show error page, idempotent for error exist check in DB', function(): void {

            $countBefore = $this->tableGateway->select()->count();

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

            $countAfter = $this->tableGateway->select()->count();

            expect($countBefore)->toBe(1);
            expect($countAfter)->toBe(1);

        });

    });

    describe('/error-preview/error', function(): void {

        it('show error page', function(): void {

            $this->tableGateway->delete([]);
            $countBefore = $this->tableGateway->select()->count();

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('http://example.com/error-preview/error');
            $request->setRequestUri('/error-preview/error');

            \ob_start();
            $this->application->run();
            $content = \ob_get_clean();

            expect($content)->toContain('<title>Error');
            expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');

            $countAfter = $this->tableGateway->select()->count();

            expect($countBefore)->toBe(0);
            expect($countAfter)->toBe(1);

        });

        it('show error page, idempotent for error exist check in DB', function(): void {

            $countBefore = $this->tableGateway->select()->count();

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('http://example.com/error-preview/error');
            $request->setRequestUri('/error-preview/error');

            \ob_start();
            $this->application->run();
            $content = \ob_get_clean();

            expect($content)->toContain('<title>Error');
            expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');

            $countAfter = $this->tableGateway->select()->count();

            expect($countBefore)->toBe(1);
            expect($countAfter)->toBe(1);

        });

    });

});
