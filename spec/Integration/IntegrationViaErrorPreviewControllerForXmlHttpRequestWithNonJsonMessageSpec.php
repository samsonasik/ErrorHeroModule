<?php

namespace ErrorHeroModule\Spec\Integration;

use ErrorHeroModule;
use ErrorHeroModule\Controller\ErrorPreviewController;
use Kahlan\Plugin\Quit;
use Kahlan\QuitException;
use Zend\Console\Console;
use Zend\Http\PhpEnvironment\Request;
use Zend\Mvc\Application;

describe('Integration via ErrorPreviewController for XmlHttpRequest with non-json message', function () {

    given('application', function () {

        Console::overrideIsConsole(false);

        $modules_add = [];
        if (class_exists(\Zend\Router\Module::class)) {
            $modules_add = [
                'Zend\Router',
                'Zend\Db'
            ];
        }

        $application = Application::init([
            'modules' => array_merge(
                $modules_add,
                [
                    'ErrorHeroModule',
                ]
            ),
            'module_listener_options' => [
                'config_glob_paths' => [
                    realpath(__DIR__).'/../Fixture/config/autoload-for-xmlhttprequest-with-non-json-message/error-hero-module.local.php',
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

            allow(Request::class)->toReceive('isXmlHttpRequest')->andReturn(true);

            ob_start();
            $closure = function () {
                $this->application->run();
            };
            expect($closure)->toThrow(new QuitException('Exit statement occurred', -1));
            $content = ob_get_clean();

            expect($content)->toBe('We have encountered a problem and we can not fulfill your request. An error report has been generated and send to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience.');

        });

    });

});
