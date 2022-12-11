<?php

namespace ErrorHeroModule\Spec\Integration;

use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Application;

describe('Integration via ErrorPreviewController for XmlHttpRequest with non-json message', function (): void {

    given('application', fn(): Application => Application::init([
        'modules' => [
            'Laminas\Router',
            'Laminas\Db',
            'ErrorHeroModule',
        ],
        'module_listener_options' => [
            'config_glob_paths' => [
                \realpath(__DIR__).'/../Fixture/config/autoload-for-xmlhttprequest-with-non-json-message/error-hero-module.local.php',
                \realpath(__DIR__).'/../Fixture/config/module.local.php',
            ],
        ],
    ]));

    describe('/error-preview', function(): void {

        it('show error page', function(): void {

            $request     = $this->application->getRequest();
            $request->setMethod('GET');
            $request->setUri('/error-preview');
            $request->setUri('http://example.com/error-preview');
            $request->setRequestUri('/error-preview');

            allow(Request::class)->toReceive('isXmlHttpRequest')->andReturn(true);
            allow(Response::class)->toReceive('getHeaders', 'addHeaderLine');

            \ob_start();
            $this->application->run();
            $content = \ob_get_clean();

            expect($content)->toBe('We have encountered a problem and we can not fulfill your request. An error report has been generated and sent to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience.');
            expect(Response::class)->toReceive('getHeaders', 'addHeaderLine')
                                   ->with('Content-type', 'text/plain');
            expect($this->application->getResponse()->getStatusCode())->toBe(500);

        });

    });

});
