<?php

namespace ErrorHeroModule\Spec\Listener;

use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Listener\Mvc;
use Kahlan\Plugin\Double;
use Kahlan\Plugin\Quit;
use Kahlan\QuitException;
use Zend\Console\Console;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\View\Renderer\PhpRenderer;
use Zend\Stdlib\SplPriorityQueue;
use Zend\Log\Writer\Db as DbWriter;
use Zend\Log\Logger;

describe('Mvc', function () {

    beforeAll(function () {
        $config = [
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
                    'message' => 'We have encountered a problem and we can not fulfill your request. An error report has been generated and send to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience.',
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

        $this->logging = Double::instance([
            'extends' => Logging::class,
            'methods' => '__construct'
        ]);

        $this->renderer = Double::instance(['extends' => PhpRenderer::class, 'methods' => '__construct']);

        $this->listener =  new Mvc(
            $config,
            $this->logging,
            $this->renderer
        );
    });

    describe('->attach()', function () {

        it('does not attach dispatch.error, render.error, and * if config[enable] = false', function () {

            $logging = Double::instance([
                'extends' => Logging::class,
                'methods' => '__construct'
            ]);

            $renderer = Double::instance(['extends' => PhpRenderer::class, 'methods' => '__construct']);

            $listener =  new Mvc(
                ['enable' => false],
                $logging,
                $renderer
            );

            $eventManager = Double::instance(['implements' => EventManagerInterface::class]);
            expect($eventManager)->not->toReceive('attach')->with(MvcEvent::EVENT_RENDER_ERROR, [$this->listener, 'exceptionError']);
            expect($eventManager)->not->toReceive('attach')->with(MvcEvent::EVENT_DISPATCH_ERROR, [$this->listener, 'exceptionError'], 100);
            expect($eventManager)->not->toReceive('attach')->with('*', [$this->listener, 'phpError']);

            $listener->attach($eventManager);

        });

        it('attach dispatch.error, render.error, and *', function () {

            $eventManager = Double::instance(['implements' => EventManagerInterface::class]);
            expect($eventManager)->toReceive('attach')->with(MvcEvent::EVENT_RENDER_ERROR, [$this->listener, 'exceptionError']);
            expect($eventManager)->toReceive('attach')->with(MvcEvent::EVENT_DISPATCH_ERROR, [$this->listener, 'exceptionError'], 100);
            expect($eventManager)->toReceive('attach')->with('*', [$this->listener, 'phpError']);

            $this->listener->attach($eventManager);

        });

    });

    describe('->exceptionError()', function () {

        it('return null for !$e->getParam("exception")', function () {

            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            allow($mvcEvent)->toReceive('getParam')->andReturn(null);

            $actual = $this->listener->exceptionError($mvcEvent);
            expect($actual)->toBeNull();

        });

        it('call logger->handleException() if $e->getParam("exception") and display_errors = 1', function () {

            $config = [
                'enable' => true,
                'display-settings' => [

                    // excluded php errors
                    'exclude-php-errors' => [
                        E_USER_DEPRECATED
                    ],

                    // show or not error
                    'display_errors'  => 1,

                    // if enable and display_errors = 0, the page will bring layout and view
                    'template' => [
                        'layout' => 'layout/layout',
                        'view'   => 'error-hero-module/error-default'
                    ],

                    // if enable and display_errors = 0, the console will bring message
                    'console' => [
                        'message' => 'We have encountered a problem and we can not fulfill your request. An error report has been generated and send to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience.',
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

            $logging = Double::instance([
                'extends' => Logging::class,
                'methods' => '__construct'
            ]);

            $renderer = Double::instance(['extends' => PhpRenderer::class, 'methods' => '__construct']);

            $listener =  new Mvc(
                $config,
                $logging,
                $renderer
            );

            $exception = new \Exception();

            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            allow($mvcEvent)->toReceive('getParam')->andReturn($exception);
            allow($logging)->toReceive('handleException')->with($exception);

            $actual = $listener->exceptionError($mvcEvent);
            expect($actual)->toBeNull(); // void

        });

        it('call logger->handleException() with default console error message if $e->getParam("exception") and display_errors = 0', function () {

            Quit::disable();
            $exception = new \Exception();

            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            allow($mvcEvent)->toReceive('getParam')->andReturn($exception);
            allow($this->logging)->toReceive('handleException')->with($exception);

            ob_start();
            $closure = function () use ($mvcEvent) {
                $this->listener->exceptionError($mvcEvent);
            };
            expect($closure)->toThrow(new QuitException());
            ob_get_clean();

        });

        it('call logger->handleException() with default view error if $e->getParam("exception") and display_errors = 0 and not a console', function () {

            Console::overrideIsConsole(false);
            Quit::disable();
            $exception = new \Exception();

            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            allow($mvcEvent)->toReceive('getParam')->andReturn($exception);
            allow($this->logging)->toReceive('handleException')->with($exception);

            ob_start();
            allow($this->renderer)->toReceive('render')->andReturn(include __DIR__ . '/../../view/error-hero-module/error-default.phtml');
            $closure = function () use ($mvcEvent) {
                $this->listener->exceptionError($mvcEvent);
            };
            expect($closure)->toThrow(new QuitException());
            ob_get_clean();

        });

    });


});
