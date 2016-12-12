<?php

namespace ErrorHeroModule\Spec\Integration;

use ErrorHeroModule;
use ErrorHeroModule\Controller\ErrorPreviewController;
use Kahlan\Plugin\Quit;
use Kahlan\QuitException;
use Zend\Console\Console;
use Zend\Http\Request;
use Zend\Mvc\Application;

describe('Integration via ErrorPreviewController for XmlHttpRequest', function () {

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
                    realpath(__DIR__).'/Fixture/autoload-for-xmlhttprequest/{{,*.}global,{,*.}local}.php',
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

            allow(Request::class)->toReceive('isXmlHttpRequest')->andReturn(true);

            ob_start();
            $closure = function () {
                $this->application->run();
            };
            expect($closure)->toThrow(new QuitException());
            $content = ob_get_clean();

            expect($content)->toBe(<<<json
{
    "error": "We have encountered a problem and we can not fulfill your request. An error report has been generated and send to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience."
}
json
            );

        });

    });

});
