<?php

namespace ErrorHeroModule\Spec\Middleware;

use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Middleware\Expressive;
use Kahlan\Plugin\Double;
use Zend\Console\Console;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Http\PhpEnvironment\Request;

describe('Expressive', function () {

    given('logging', function () {
        return Double::instance([
            'extends' => Logging::class,
            'methods' => '__construct'
        ]);
    });

    given('renderer', function () {
        return Double::instance(['implements' => TemplateRendererInterface::class]);
    });

    given('config', function () {
        return [
            'enable' => true,
            'display-settings' => [

                // excluded php errors
                'exclude-php-errors' => [
                    E_USER_DEPRECATED
                ],

                // show or not error
                'display_errors'  => 0,

                // if enable and display_errors = 0, the page will bring layout and view
                'template' => [
                    'layout' => 'layout/layout',
                    'view'   => 'error-hero-module/error-default'
                ],

                // if enable and display_errors = 0, the console will bring message
                'console' => [
                    'message' => 'We have encountered a problem and we can not fulfill your request. An error report has been generated and sent to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience.',
                ],

            ],
            'logging-settings' => [
                'same-error-log-time-range' => 86400,
            ],
            'email-notification-settings' => [
                // set to true to activate email notification on log error
                'enable' => false,

                // Zend\Mail\Message instance registered at service manager
                'mail-message'   => 'YourMailMessageService',

                // Zend\Mail\Transport\TransportInterface instance registered at service manager
                'mail-transport' => 'YourMailTransportService',

                // email sender
                'email-from'    => 'Sender Name <sender@host.com>',

                'email-to-send' => [
                    'developer1@foo.com',
                    'developer2@foo.com',
                ],
            ],
        ];
    });

    given('middleware', function () {
        return new Expressive(
            $this->config,
            $this->logging,
            $this->renderer
        );
    });

    describe('->__invoke()', function () {

        it('returns next() when not enabled', function () {

            $config['enable'] = false;

            $request  = new ServerRequest();
            $response = new Response();
            $next     = function ($request, $response) {
                return new Response();
            };
            $middleware = new Expressive($config, $this->logging, $this->renderer);

            $actual = $middleware->__invoke($request, $response, $next);
            expect($actual)->toBeAnInstanceOf(Response::class);

        });

        it('returns next() when no error', function () {

            $request  = new ServerRequest();
            $response = new Response();
            $next     = function ($request, $response) {
                return new Response();
            };

            $actual = $this->middleware->__invoke($request, $response, $next);
            expect($actual)->toBeAnInstanceOf(Response::class);

        });

    });

});
