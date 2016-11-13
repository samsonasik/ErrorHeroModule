<?php

namespace ErrorHeroModule\Spec\Listener;

use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Listener\Mvc;
use Kahlan\Plugin\Double;
use Kahlan\Plugin\Quit;
use Kahlan\QuitException;
use Zend\Console\Console;
use Zend\EventManager\EventManagerInterface;
use Zend\Log\Logger;
use Zend\Mvc\MvcEvent;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver;
use Zend\Log\Writer\Db as DbWriter;
use ReflectionProperty;
use Zend\Stdlib\SplPriorityQueue;
use Zend\Http\PhpEnvironment\Request;
use Zend\ServiceManager\ServiceLocatorInterface;

describe('Mvc', function () {

    given('logging', function () {
        return Double::instance([
            'extends' => Logging::class,
            'methods' => '__construct'
        ]);
    });

    given('renderer', function () {
        return Double::instance(['extends' => PhpRenderer::class, 'methods' => '__construct']);
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
    });

    given('listener', function () {
        return new Mvc(
            $this->config,
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

            $renderer = new PhpRenderer();
            $resolver = new Resolver\AggregateResolver();

            $map = new Resolver\TemplateMapResolver([
                'layout/layout'                   => __DIR__ . '/../Fixture/view/layout/layout.phtml',
                'error-hero-module/error-default' => __DIR__ . '/../Fixture/view/error-hero-module/error-default.phtml',
            ]);
            $resolver->attach($map);
            $renderer->setResolver($resolver);

            $listener =  new Mvc(
                $this->config,
                $this->logging,
                $renderer
            );

            ob_start();
            $closure = function () use ($listener, $mvcEvent) {
                $listener->exceptionError($mvcEvent);
            };
            expect($closure)->toThrow(new QuitException());
            $content = ob_get_clean();

            expect($content)->toContain('We have encountered a problem');
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
            $content = ob_get_clean();

            expect($content)->toContain('We have encountered a problem');

        });

    });

    describe('->phpError()', function () {

        it('set error_reporting & ini_set(display_errors) when display_errors config = 0', function () {

                expect('error_reporting')->toBeCalled();
                expect('ini_set')->toBeCalled();

                $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
                $this->listener->phpError($mvcEvent);

        });

        it('not set error_reporting & ini_set(display_errors) when display_errors config = 0', function () {

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

                expect('error_reporting')->not->toBeCalled();
                expect('ini_set')->not->toBeCalled();

                $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
                $listener->phpError($mvcEvent);

        });

    });

    describe('->execOnShutdown()', function ()  {

        it('call error_get_last() and return nothing', function () {

            allow('error_get_last')->toBeCalled();
            expect('error_get_last')->toBeCalled();

            $this->listener->execOnShutdown();

        });

        it('call error_get_last() and return error', function () {

            skipIf(PHP_MAJOR_VERSION < 7);

            allow('error_get_last')->toBeCalled()->andReturn([
                'type' => 8,
                'message' => 'Undefined variable: a',
                'file' => '/var/www/zf/module/Application/Module.php',
                'line' => 2
            ]);
            expect('error_get_last')->toBeCalled();

            try {
                $this->listener->execOnShutdown();
            } catch (\Throwable $t) {
                expect($t)->toBeAnInstanceOf(\Throwable::class);
            }
        });


    });

});
