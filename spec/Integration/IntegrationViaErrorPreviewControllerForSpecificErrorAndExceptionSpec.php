<?php

namespace ErrorHeroModule\Spec\Integration;

use Laminas\Mvc\Application;

describe('Integration via ErrorPreviewController', function (): void {

    given('application', function () {

        $application = Application::init([
            'modules' => [
                'Laminas\Router',
                'Laminas\Db',
                'ErrorHeroModule',
            ],
            'module_listener_options' => [
                'config_glob_paths' => [
                    \realpath(__DIR__).'/../Fixture/config/autoload-for-specific-error-and-exception/error-hero-module.local.php',
                    \realpath(__DIR__).'/../Fixture/config/module.local.php',
                ],
            ],
        ]);

        $eventManager         = $application->getEventManager();
        $serviceManager = $application->getServiceManager();
        $serviceManager->get('SendResponseListener')
                       ->detach($eventManager);

        return $application;

    });

    describe('/error-preview', function(): void {

        it('empty as rely to original mvc process to handle', function(): void {

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('http://example.com/error-preview');
            $request->setRequestUri('/error-preview');

            \ob_start();
            $this->application->run();
            $content = \ob_get_clean();

            expect(\ob_get_clean())->toBe('');
            expect($this->application->getResponse()->getStatusCode())->toBe(500);

        });

    });

    describe('/error-preview/warning', function(): void {

        it('empty as rely to original mvc process to handle', function(): void {

            @mkdir(__DIR__ . '/../Fixture/view/error-hero-module/error-preview', 0755, true);
            file_put_contents(__DIR__ . '/../Fixture/view/error-hero-module/error-preview/warning.phtml', '');

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('http://example.com/error-preview/warning');
            $request->setRequestUri('/error-preview/warning');

            \ob_start();
            $this->application->run();
            $content = \ob_get_clean();

            expect(\ob_get_clean())->toBe('');
            // yes, excluded E_* error means it ignored, means it passed, means it is 200 status code
            expect($this->application->getResponse()->getStatusCode())->toBe(200);

            unlink(__DIR__ . '/../Fixture/view/error-hero-module/error-preview/warning.phtml');
            rmdir(__DIR__ . '/../Fixture/view/error-hero-module/error-preview');
            rmdir(__DIR__ . '/../Fixture/view/error-hero-module');

        });

    });

    describe('/error-preview/error', function(): void {

        it('empty as rely to original mvc process to handle', function(): void {

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
