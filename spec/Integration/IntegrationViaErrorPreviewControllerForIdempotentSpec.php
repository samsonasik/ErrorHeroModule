<?php

namespace ErrorHeroModule\Spec;

use ErrorHeroModule;
use ErrorHeroModule\Controller\ErrorPreviewController;
use Zend\Console\Console;
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
                    realpath(__DIR__).'/../Fixture/config/autoload/error-hero-module.local.php',
                    realpath(__DIR__).'/../Fixture/config/module.local.php',
                ],
            ],
        ]);

        return $application;

    });

    describe('/error-preview', function() {

        it('show error page', function() {

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('/error-preview');

            ob_start();
            $this->application->run();
            $content = ob_get_clean();

            expect($content)->toContain('<title>Error');
            expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');

        });

        it('show error page, idempotent for error exist check in DB', function() {

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

        it('show error page', function() {

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('/error-preview/error');

            ob_start();
            $this->application->run();
            $content = ob_get_clean();

            expect($content)->toContain('<title>Error');
            expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');

        });

        it('show error page, idempotent for error exist check in DB', function() {

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
