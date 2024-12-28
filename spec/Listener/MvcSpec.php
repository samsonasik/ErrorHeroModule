<?php

namespace ErrorHeroModule\Spec\Listener;

use Closure;
use ErrorException;
use ErrorHeroModule\Compat\Logger;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Listener\Mvc;
use Exception;
use Kahlan\Plugin\Double;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Log\Writer\Db;
use Laminas\Mvc\MvcEvent;
use Laminas\Uri\Uri;
use Laminas\View\Renderer\PhpRenderer;
use PDO;

describe('Mvc', function (): void {

    given('logging', fn() : object => Double::instance([
        'extends' => Logging::class,
        'methods' => '__construct'
    ]));

    given('renderer', fn() : object => Double::instance(['extends' => PhpRenderer::class, 'methods' => '__construct']));

    given('config', fn() : array => [
        'enable' => true,
        'display-settings' => [

            // excluded php errors
            'exclude-php-errors' => [
                \E_USER_DEPRECATED
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

            // Laminas\Mail\Message instance registered at service manager
            'mail-message'   => 'YourMailMessageService',

            // Laminas\Mail\Transport\TransportInterface instance registered at service manager
            'mail-transport' => 'YourMailTransportService',

            // email sender
            'email-from'    => 'Sender Name <sender@host.com>',

            'email-to-send' => [
                'developer1@foo.com',
                'developer2@foo.com',
            ],
        ],
    ]);

    given('listener', fn() : Mvc => new Mvc(
        $this->config,
        $this->logging,
        $this->renderer
    ));

    describe('->attach()', function (): void {

        it('does not attach dispatch.error, render.error, and bootstrap if config[enable] = false', function (): void {

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
            expect($eventManager)->not->toReceive('attach')->with(MvcEvent::EVENT_RENDER_ERROR, [$listener, 'exceptionError']);
            expect($eventManager)->not->toReceive('attach')->with(MvcEvent::EVENT_DISPATCH_ERROR, [$listener, 'exceptionError'], 100);
            expect($eventManager)->not->toReceive('attach')->with(MvcEvent::EVENT_BOOTSTRAP, [$listener, 'phpError']);

            $listener->attach($eventManager);

        });

        it('attach dispatch.error, render.error, and bootstrap', function (): void {

            $eventManager = Double::instance(['implements' => EventManagerInterface::class]);
            expect($eventManager)->toReceive('attach')->with(MvcEvent::EVENT_RENDER_ERROR, [$this->listener, 'exceptionError']);
            expect($eventManager)->toReceive('attach')->with(MvcEvent::EVENT_DISPATCH_ERROR, [$this->listener, 'exceptionError'], 100);
            expect($eventManager)->toReceive('attach')->with(MvcEvent::EVENT_BOOTSTRAP, [$this->listener, 'phpError']);

            $this->listener->attach($eventManager);

        });

    });

    describe('->exceptionError()', function (): void {

        it('return null for !$e->getParam("exception")', function (): void {

            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            allow($mvcEvent)->toReceive('getParam')->andReturn(null);

            $actual = $this->listener->exceptionError($mvcEvent, Double::instance(['extends' => Request::class, 'methods' => '__construct']));
            expect($actual)->toBeNull();

        });

        it('call logging->handleErrorException() if $e->getParam("exception") and display_errors = 1', function (): void {

            $config = $this->config;
            $config['display-settings']['display_errors'] = 1;

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

            $exception = new Exception('message');

            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            allow($mvcEvent)->toReceive('getParam')->andReturn($exception);
            $request = new Request();
            allow($mvcEvent)->toReceive('getRequest')->andReturn($request);
            allow($logging)->toReceive('handleErrorException')->with($exception, $request);

            expect($listener->exceptionError($mvcEvent))->toBeNull();

        });

        it('call logging->handleErrorException() with default view error if $e->getParam("exception") and display_errors = 0', function (): void {

            $exception = new Exception('message');

            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            allow($mvcEvent)->toReceive('getParam')->andReturn($exception);
            $request = new Request();
            allow($mvcEvent)->toReceive('getRequest')->andReturn($request);
            allow($this->logging)->toReceive('handleErrorException')->with($exception, $request);

            \ob_start();
            allow($this->renderer)->toReceive('render')->andReturn(include __DIR__ . '/../../view/error-hero-module/error-default.phtml');
            $closure = function () use ($mvcEvent): void {
                $this->listener->exceptionError($mvcEvent, Double::instance(['extends' => Request::class, 'methods' => '__construct']));
            };
            $content = \ob_get_clean();

            expect($content)->toContain('<p>We have encountered a problem');

        });

        it('do not call logging->handleErrorException() if $e->getParam("exception") and has excluded exception match', function (): void {

            $config = $this->config;
            $config['display-settings']['exclude-exceptions'] = [Exception::class];

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

            $exception = new Exception('message');

            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            allow($mvcEvent)->toReceive('getParam')->andReturn($exception);
            expect($listener->exceptionError($mvcEvent))->toBeNull();
            expect($logging)->not->toReceive('handleErrorException');

        });

    });

    describe('->phpFatalErrorHandler()', function (): void  {

        it('returns buffer on no error', function (): void {

            allow('error_get_last')->toBeCalled()->andReturn(null);
            expect($this->listener->phpFatalErrorHandler('root'))->toBe('root');

        });

        it('returns buffer on error has "Uncaught" prefix', function (): void {

            allow('error_get_last')->toBeCalled()->andReturn([
                'message' => 'Uncaught',
                'type'    => 3,
            ]);
            expect($this->listener->phpFatalErrorHandler('Uncaught'))->toBe('Uncaught');

        });

        it('returns message value on error not has "Uncaught" prefix and result is empty', function (): void {

            allow('error_get_last')->toBeCalled()->andReturn([
                'message' => 'Fatal',
            ]);

            expect($this->listener->phpFatalErrorHandler('Fatal'))->toBe('Fatal');

        });

    });

    describe('->execOnShutdown()', function (): void  {

        it('call error_get_last() and return nothing and no result', function (): void {

            allow('error_get_last')->toBeCalled()->andReturn(null);
            expect($this->listener->execOnShutdown())->toBeNull();

        });

        it('call error_get_last() and return nothing on result with "Uncaught" prefix', function (): void {

            allow('error_get_last')->toBeCalled()->andReturn([
                'message' => 'Uncaught',
                'type'    => 3,
            ]);
            expect($this->listener->execOnShutdown())->toBeNull();

        });

        it('call error_get_last() and property_exists() after null check passed', function (): void {

            allow('error_get_last')->toBeCalled()->andReturn([
                'type' => 3,
                'message' => 'class@anonymous cannot implement stdClass - it is not an interface',
                'file' => '/var/www/zf/module/Application/Module.php',
                'line' => 2
            ]);

            $dbAdapter = new Adapter([
                'username' => 'root',
                'password' => '',
                'driver' => 'Pdo',
                'dsn' => 'mysql:dbname=errorheromodule;host=127.0.0.1',
                'driver_options' => [
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
                ],
            ]);

            $writer = new Db(
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
                        'db'     => AdapterInterface::class,
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
                $this->config,
                $logWritersConfig,
                null,
                null,
                true
            );

            $errorHeroModuleLocalConfig  = [
                'enable' => true,
                'display-settings' => [
                    'exclude-php-errors' => [
                        \E_USER_DEPRECATED
                    ],
                    'display_errors'  => 1,
                    'template' => [
                        'layout' => 'layout/layout',
                        'view'   => 'error-hero-module/error-default'
                    ],
                    'console' => [
                        'message' => 'We have encountered a problem and we can not fulfill your request. An error report has been generated and sent to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience.',
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
            allow('property_exists')->toBeCalled()->with($listener, 'request')->andReturn(false);
            allow('property_exists')->toBeCalled()->with($listener, 'mvcEvent')->andReturn(true);

            $mvcEvent = & Closure::bind(fn&($listener) => $listener->mvcEvent, null, $listener)($listener);
            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);

            $request = new Request();
            allow($mvcEvent)->toReceive('getRequest')->andReturn($request);
            allow($request)->toReceive('getUri')->andReturn(new Uri('http://example.com/'));

            expect($listener->execOnShutdown())->toBeNull();

        });


    });

    describe('->phpErrorHandler()', function (): void {

        it('error_reporting() returns 0', function (): void {

            allow('error_reporting')->tobeCalled()->andReturn(0);
            $actual = $this->listener->phpErrorHandler(2, 'mkdir(): File exists', 'file.php', 6);
            // null means use default mvc process
            expect($actual)->toBeNull();

        });

        it('throws ErrorException on non excluded php errors', function (): void {

            $closure = function (): void {
                 $this->listener->phpErrorHandler(\E_WARNING, 'warning', 'file.php', 1);
            };
            expect($closure)->toThrow(new ErrorException('warning', 0, \E_WARNING, 'file.php', 1));

        });

    });

});
