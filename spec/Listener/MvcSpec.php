<?php

namespace ErrorHeroModule\Spec\Listener;

use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Listener\Mvc;
use Kahlan\Plugin\Double as DoublePlugin;
use Kahlan\Plugin\Quit;
use Kahlan\QuitException;
use Zend\Console\Console;
use Zend\Db\Adapter\Adapter;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\PhpEnvironment\Request;
use Zend\Log\Logger;
use Zend\Log\Writer\Db as DbWriter;
use Zend\Mvc\MvcEvent;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver;

describe('Mvc', function () {

    given('logging', function () {
        return DoublePlugin::instance([
            'extends' => Logging::class,
            'methods' => '__construct'
        ]);
    });

    given('renderer', function () {
        return DoublePlugin::instance(['extends' => PhpRenderer::class, 'methods' => '__construct']);
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

            $logging = DoublePlugin::instance([
                'extends' => Logging::class,
                'methods' => '__construct'
            ]);

            $renderer = DoublePlugin::instance(['extends' => PhpRenderer::class, 'methods' => '__construct']);

            $listener =  new Mvc(
                ['enable' => false],
                $logging,
                $renderer
            );

            $eventManager = DoublePlugin::instance(['implements' => EventManagerInterface::class]);
            expect($eventManager)->not->toReceive('attach')->with(MvcEvent::EVENT_RENDER_ERROR, [$this->listener, 'exceptionError']);
            expect($eventManager)->not->toReceive('attach')->with(MvcEvent::EVENT_DISPATCH_ERROR, [$this->listener, 'exceptionError'], 100);
            expect($eventManager)->not->toReceive('attach')->with('*', [$this->listener, 'phpError']);

            $listener->attach($eventManager);

        });

        it('attach dispatch.error, render.error, and *', function () {

            $eventManager = DoublePlugin::instance(['implements' => EventManagerInterface::class]);
            expect($eventManager)->toReceive('attach')->with(MvcEvent::EVENT_RENDER_ERROR, [$this->listener, 'exceptionError']);
            expect($eventManager)->toReceive('attach')->with(MvcEvent::EVENT_DISPATCH_ERROR, [$this->listener, 'exceptionError'], 100);
            expect($eventManager)->toReceive('attach')->with('*', [$this->listener, 'phpError']);

            $this->listener->attach($eventManager);

        });

    });

    describe('->exceptionError()', function () {

        it('return null for !$e->getParam("exception")', function () {

            $mvcEvent = DoublePlugin::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
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

            $logging = DoublePlugin::instance([
                'extends' => Logging::class,
                'methods' => '__construct'
            ]);

            $renderer = DoublePlugin::instance(['extends' => PhpRenderer::class, 'methods' => '__construct']);

            $listener =  new Mvc(
                $config,
                $logging,
                $renderer
            );

            $exception = new \Exception();

            $mvcEvent = DoublePlugin::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            allow($mvcEvent)->toReceive('getParam')->andReturn($exception);
            allow($logging)->toReceive('handleException')->with($exception);

            $actual = $listener->exceptionError($mvcEvent);
            expect($actual)->toBeNull(); // void

        });

        it('call logger->handleException() with default console error message if $e->getParam("exception") and display_errors = 0', function () {

            Quit::disable();
            $exception = new \Exception();

            $mvcEvent = DoublePlugin::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
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
            expect($closure)->toThrow(new QuitException('Exit statement occurred', -1));
            $content = ob_get_clean();

            expect($content)->toContain('We have encountered a problem');
        });

        it('call logger->handleException() with default view error if $e->getParam("exception") and display_errors = 0 and not a console', function () {

            Console::overrideIsConsole(false);
            Quit::disable();
            $exception = new \Exception();

            $mvcEvent = DoublePlugin::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            allow($mvcEvent)->toReceive('getParam')->andReturn($exception);
            allow($this->logging)->toReceive('handleException')->with($exception);

            ob_start();
            allow($this->renderer)->toReceive('render')->andReturn(include __DIR__ . '/../../view/error-hero-module/error-default.phtml');
            $closure = function () use ($mvcEvent) {
                $this->listener->exceptionError($mvcEvent);
            };
            expect($closure)->toThrow(new QuitException('Exit statement occurred', -1));
            $content = ob_get_clean();

            expect($content)->toContain('We have encountered a problem');

        });

    });

    describe('->execOnShutdown()', function ()  {

        it('call error_get_last() and return nothing', function () {

            allow('error_get_last')->toBeCalled();
            expect('error_get_last')->toBeCalled();

            $this->listener->execOnShutdown();

        });

        it('call error_get_last() and return error', function () {

            allow('error_get_last')->toBeCalled()->andReturn([
                'type' => 8,
                'message' => 'Undefined variable: a',
                'file' => '/var/www/zf/module/Application/Module.php',
                'line' => 2
            ]);
            expect('error_get_last')->toBeCalled();

            $dbAdapter = new Adapter([
                'username' => 'root',
                'password' => '',
                'driver' => 'Pdo',
                'dsn' => 'mysql:dbname=errorheromodule;host=127.0.0.1',
                'driver_options' => [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
                ],
            ]);

            $writer = new DbWriter(
                [
                    'db' => $dbAdapter,
                    'table' => 'log',
                    'column' => [
                        'timestamp' => 'date',
                        'priority'  => 'type',
                        'message'   => 'event',
                        'extra'     => [
                            'url'  => 'url',
                            'file' => 'file',
                            'line' => 'line',
                            'error_type' => 'error_type',
                            'trace'      => 'trace',
                            'request_data' => 'request_data',
                        ],
                    ],
                ]
            );

            $logger = new Logger();
            $logger->addWriter($writer);
            $logWritersConfig = [

                [
                    'name' => 'db',
                    'options' => [
                        'db'     => 'Zend\Db\Adapter\Adapter',
                        'table'  => 'log',
                        'column' => [
                            'timestamp' => 'date',
                            'priority'  => 'type',
                            'message'   => 'event',
                            'extra'     => [
                                'url'  => 'url',
                                'file' => 'file',
                                'line' => 'line',
                                'error_type' => 'error_type',
                                'trace'      => 'trace',
                                'request_data' => 'request_data',
                            ],
                        ],
                    ],
                ],

            ];

            $logging = new Logging(
                $logger,
                'http://serverUrl',
                DoublePlugin::instance(['extends' => Request::class, 'methods' => '__construct']),
                '/',
                $this->config,
                $logWritersConfig,
                null,
                null
            );

            $errorHeroModuleLocalConfig  = [
                'enable' => true,
                'display-settings' => [
                    'exclude-php-errors' => [
                        E_USER_DEPRECATED
                    ],
                    'display_errors'  => 1,
                    'template' => [
                        'layout' => 'layout/layout',
                        'view'   => 'error-hero-module/error-default'
                    ],
                    'console' => [
                        'message' => 'We have encountered a problem and we can not fulfill your request. An error report has been generated and send to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience.',
                    ],

                ],
                'logging-settings' => [
                    'same-error-log-time-range' => 86400,
                ],
                'email-notification-settings' => [
                    'enable' => false,
                    'mail-message'   => 'YourMailMessageService',
                    'mail-transport' => 'YourMailTransportService',
                    'email-from'    => 'Sender Name <sender@host.com>',
                    'email-to-send' => [
                        'developer1@foo.com',
                        'developer2@foo.com',
                    ],
                ],
            ];

            $listener = new Mvc(
                $errorHeroModuleLocalConfig,
                $logging,
                $this->renderer
            );
            $listener->execOnShutdown();

        });


    });

});
