<?php

namespace ErrorHeroModule\Spec\Middleware;

use Closure;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Middleware\Expressive;
use Kahlan\Plugin\Double;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Db\Adapter\Adapter;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;
use Zend\Expressive\ZendView\ZendViewRenderer;
use Zend\Http\PhpEnvironment\Request;
use Zend\Log\Logger;
use Zend\Log\Writer\Db as DbWriter;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver;

describe('Expressive', function () {

    given('logging', function () {
        return Double::instance([
            'extends' => Logging::class,
            'methods' => '__construct'
        ]);
    });

    given('renderer', function () {

        $renderer = new PhpRenderer();
        $resolver = new Resolver\AggregateResolver();

        $map = new Resolver\TemplateMapResolver([
            'layout/layout'                   => __DIR__ . '/../Fixture/view/layout/layout.phtml',
            'error-hero-module/error-default' => __DIR__ . '/../../view/error-hero-module/error-default.phtml',
        ]);
        $resolver->attach($map);
        $renderer->setResolver($resolver);

        return new ZendViewRenderer($renderer);

    });

    given('logger', function () {

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

        return $logger;

    });

    given('config', function () {
        return [
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

                // for expressive, when container doesn't has \Zend\Expressive\Template\TemplateRendererInterface service
                // if enable, and display_errors = 0, then show a message under no_template config
                'no_template' => [
                    'message' => <<<json
{
    "type": "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",
    "title": "Internal Server Error",
    "status": 500,
    "detail": "We have encountered a problem and we can not fulfill your request. An error report has been generated and sent to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience."
}
json
                ],

                'ajax' => [
                    'message' => <<<json
{
    "type": "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",
    "title": "Internal Server Error",
    "status": 500,
    "detail": "We have encountered a problem and we can not fulfill your request. An error report has been generated and sent to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience."
}
json
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

    given('logWritersConfig', function () {

        $logWritersConfig = [

            [
                'name' => 'db',
                'options' => [
                    'db'     => Adapter::class,
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

        return $logWritersConfig;

    });

    given('middleware', function () {
        return new Expressive(
            $this->config,
            $this->logging,
            $this->renderer
        );
    });

    describe('->process()', function () {

        it('returns handle() when not enabled', function () {

            $config['enable'] = false;

            $request = new ServerRequest(
                [],
                [],
                new Uri('http://example.com'),
                'GET',
                'php://memory',
                [],
                [],
                [],
                '',
                '1.2'
            );
            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($request)->andReturn(new Response());
            $middleware = new Expressive($config, $this->logging, $this->renderer);

            $actual = $middleware->process($request, $handler);
            expect($actual)->toBeAnInstanceOf(ResponseInterface::class);

        });

        it('returns handle() when no error', function () {

            $request  = new ServerRequest(
                [],
                [],
                new Uri('http://example.com'),
                'GET',
                'php://memory',
                [],
                [],
                [],
                '',
                '1.2'
            );
            $handler  = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($request)->andReturn(new Response());

            allow(Logging::class)->toReceive('setServerRequestandRequestUri')->with($request);

            $actual = $this->middleware->process($request, $handler);
            expect($actual)->toBeAnInstanceOf(ResponseInterface::class);

        });

        context('error', function () {

            it('non-xmlhttprequest: returns error page on display_errors = 0', function () {

                $config = $this->config;
                $config['display-settings']['display_errors'] = 0;

                $this->logWritersConfig = [

                    [
                        'name' => 'db',
                        'options' => [
                            'db'     => Adapter::class,
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
                    $this->logger,
                    $config,
                    $this->logWritersConfig,
                    null,
                    null
                );

                $request  = new ServerRequest(
                    [],
                    [],
                    new Uri('http://example.com'),
                    'GET',
                    'php://memory',
                    [],
                    [],
                    [],
                    '',
                    '1.2'
                );
                $handler  = Double::instance(['implements' => RequestHandlerInterface::class]);
                allow($handler)->toReceive('handle')->with($request)->andRun(function () {
                    throw new \Exception('message');
                });
                $middleware = new Expressive($config, $logging, $this->renderer);

                $actual = $middleware->process($request, $handler);
                expect($actual)->toBeAnInstanceOf(Response::class);

                $content = $actual->getBody()->__toString();
                expect($content)->toContain('<title>Error');
                expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');

            });

            it('non-xmlhttprequest: shows error on display_errors = 1', function () {

                $config = $this->config;
                $config['display-settings']['display_errors'] = 1;

                $logging = new Logging(
                    $this->logger,
                    $config,
                    $this->logWritersConfig,
                    null,
                    null
                );

                $request  = new ServerRequest(
                    [],
                    [],
                    new Uri('http://example.com'),
                    'GET',
                    'php://memory',
                    [],
                    [],
                    [],
                    '',
                    '1.2'
                );
                $handler  = Double::instance(['implements' => RequestHandlerInterface::class]);
                allow($handler)->toReceive('handle')->with($request)->andRun(function () {
                    throw new \Exception('message');
                });
                $middleware = new Expressive($config, $logging, $this->renderer);

                $closure = function () use ($middleware, $request, $handler) {
                    $middleware->process($request, $handler);
                };
                expect($closure)->toThrow(new \Exception('message'));

            });

            it('passed renderer is null returns error message on display_errors = 0', function () {

                $config = $this->config;
                $config['display-settings']['display_errors'] = 0;

                $logging = new Logging(
                    $this->logger,
                    $config,
                    $this->logWritersConfig,
                    null,
                    null
                );

                $request  = new ServerRequest(
                    [],
                    [],
                    new Uri('http://example.com'),
                    'GET',
                    'php://memory',
                    [],
                    [],
                    [],
                    '',
                    '1.2'
                );
                $request  = $request->withHeader('X-Requested-With', 'XmlHttpRequest');
                $handler  = Double::instance(['implements' => RequestHandlerInterface::class]);
                allow($handler)->toReceive('handle')->with($request)->andRun(function () {
                    throw new \Exception('message');
                });
                $middleware = new Expressive($config, $logging, null);

                $actual = $middleware->process($request, $handler);
                expect($actual)->toBeAnInstanceOf(Response::class);
                expect($actual->getBody()->__toString())->toBe(<<<json
{
    "type": "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",
    "title": "Internal Server Error",
    "status": 500,
    "detail": "We have encountered a problem and we can not fulfill your request. An error report has been generated and sent to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience."
}
json
                );

            });

            it('xmlhttprequest: returns error page on display_errors = 0', function () {

                $config = $this->config;
                $config['display-settings']['display_errors'] = 0;

                $logging = new Logging(
                    $this->logger,
                    $config,
                    $this->logWritersConfig,
                    null,
                    null
                );

                $request  = new ServerRequest(
                    [],
                    [],
                    new Uri('http://example.com'),
                    'GET',
                    'php://memory',
                    [],
                    [],
                    [],
                    '',
                    '1.2'
                );
                $request  = $request->withHeader('X-Requested-With', 'XmlHttpRequest');
                $handler  = Double::instance(['implements' => RequestHandlerInterface::class]);
                allow($handler)->toReceive('handle')->with($request)->andRun(function () {
                    throw new \Exception('message');
                });
                $middleware = new Expressive($config, $logging, $this->renderer);

                $actual = $middleware->process($request, $handler);
                expect($actual)->toBeAnInstanceOf(Response::class);
                expect($actual->getBody()->__toString())->toBe(<<<json
{
    "type": "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",
    "title": "Internal Server Error",
    "status": 500,
    "detail": "We have encountered a problem and we can not fulfill your request. An error report has been generated and sent to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience."
}
json
                );

            });

            it('xmlhttprequest: shows error on display_errors = 1', function () {

                $config = $this->config;
                $config['display-settings']['display_errors'] = 1;

                $logging = new Logging(
                    $this->logger,
                    $config,
                    $this->logWritersConfig,
                    null,
                    null
                );

                $request  = new ServerRequest(
                    [],
                    [],
                    new Uri('http://example.com'),
                    'GET',
                    'php://memory',
                    [],
                    [],
                    [],
                    '',
                    '1.2'
                );
                $request  = $request->withHeader('X-Requested-With', 'XmlHttpRequest');
                $handler  = Double::instance(['implements' => RequestHandlerInterface::class]);
                allow($handler)->toReceive('handle')->with($request)->andRun(function () {
                    throw new \Exception('message');
                });
                $middleware = new Expressive($config, $logging, $this->renderer);

                $closure = function () use ($middleware, $request, $handler) {
                    $middleware->process($request, $handler);
                };
                expect($closure)->toThrow(new \Exception('message'));

            });

        });

    });

    describe('->exceptionError()', function () {

        it('do not call logging->handleErrorException() if $e->getParam("exception") and has excluded exception match', function () {

            $config = $this->config;
            $config['display-settings']['exclude-exceptions'] = [
                \Exception::class
            ];
            $exception = new \Exception('message');

            $logging = new Logging(
                $this->logger,
                $config,
                $this->logWritersConfig,
                null,
                null
            );

            $request  = new ServerRequest(
                [],
                [],
                new Uri('http://example.com'),
                'GET',
                'php://memory',
                [],
                [],
                [],
                '',
                '1.2'
            );
            $request  = $request->withHeader('X-Requested-With', 'XmlHttpRequest');
            $handler  = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($request)->andRun(function () use ($exception) {
                throw $exception;
            });
            $middleware = new Expressive($config, $logging, $this->renderer);
            $closure = function () use ($middleware, $request, $handler) {
                $middleware->process($request, $handler);
            };
            expect($closure)->toThrow($exception);
            expect($logging)->not->toReceive('handleErrorException');

        });

    });

    describe('->phpFatalErrorHandler()', function ()  {

        it('returns buffer on no error', function () {

            allow('error_get_last')->toBeCalled()->andReturn(null);
            expect($this->middleware->phpFatalErrorHandler('test'))->toBe('test');

        });

        it('returns buffer on error has "Uncaught" prefix', function () {

            allow('error_get_last')->toBeCalled()->andReturn([
                'message' => 'Uncaught',
                'type'    => 3,
            ]);
            expect($this->middleware->phpFatalErrorHandler('Uncaught'))->toBe('Uncaught');

        });

        it('returns result property value on error not has "Uncaught" prefix', function () {

            allow('error_get_last')->toBeCalled()->andReturn([
                'message' => 'Fatal',
            ]);
            expect($this->middleware->phpFatalErrorHandler('Fatal'))->toBe('');

        });

    });

    describe('->execOnShutdown()', function ()  {

        it('call error_get_last() and return nothing', function () {

            allow('error_get_last')->toBeCalled()->andReturn(null);
            expect($this->middleware->execOnShutdown())->toBeNull();

        });

        it('call error_get_last() and property_exists() after null check passed and throws', function () {

            allow('error_get_last')->toBeCalled()->andReturn([
                'type' => 3,
                'message' => 'class@anonymous cannot implement stdClass - it is not an interface',
                'file' => '/var/www/zf/templates/app/home-page.phtml',
                'line' => 2
            ]);

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
                        'db'     => Adapter::class,
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
                null
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

            $middleware = new Expressive(
                $errorHeroModuleLocalConfig,
                $logging,
                $this->renderer
            );

            allow('property_exists')->toBeCalled()->with($middleware, 'request')->andReturn(true);
            allow('property_exists')->toBeCalled()->with($middleware, 'mvcEvent')->andReturn(false);

            $request = & Closure::bind(function & ($middleware) {
                return $middleware->request;
            }, null, $middleware)($middleware);
            $request = new ServerRequest(
                [],
                [],
                new Uri('http://example.com'),
                'GET',
                'php://memory',
                [],
                [],
                [],
                '',
                '1.2'
            );

            $closure = function () use ($middleware) {
                $middleware->execOnShutdown();
            };
            expect($closure)->toThrow(new \ErrorException(
                'class@anonymous cannot implement stdClass - it is not an interface'
            ));

        });

        it('call error_get_last() and property_exists() after null check passed', function () {

            allow('error_get_last')->toBeCalled()->andReturn([
                'type' => 3,
                'message' => 'class@anonymous cannot implement stdClass - it is not an interface',
                'file' => '/var/www/zf/templates/app/home-page.phtml',
                'line' => 2
            ]);

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
                        'db'     => Adapter::class,
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
                null
            );

            $errorHeroModuleLocalConfig  = [
                'enable' => true,
                'display-settings' => [
                    'exclude-php-errors' => [
                        \E_USER_DEPRECATED
                    ],
                    'display_errors'  => 0,
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

            $middleware = new Expressive(
                $errorHeroModuleLocalConfig,
                $logging,
                $this->renderer
            );

            allow('property_exists')->toBeCalled()->with($middleware, 'request')->andReturn(true);
            allow('property_exists')->toBeCalled()->with($middleware, 'mvcEvent')->andReturn(false);

            $request = & Closure::bind(function & ($middleware) {
                return $middleware->request;
            }, null, $middleware)($middleware);
            $request = new ServerRequest(
                [],
                [],
                new Uri('http://example.com'),
                'GET',
                'php://memory',
                [],
                [],
                [],
                '',
                '1.2'
            );

            expect($middleware->execOnShutdown())->toBeNull();

        });


    });

    describe('->phpErrorHandler()', function () {

        it('error_reporting() returns 0', function () {

            allow('error_reporting')->tobeCalled()->andReturn(0);
            $actual = $this->middleware->phpErrorHandler(2, 'mkdir(): File exists', 'file.php', 6);
            // null means use default $handler->handle($request)
            expect($actual)->toBeNull();

        });

        it('call error_get_last() and return nothing on result with "Uncaught" prefix', function () {

            allow('error_get_last')->toBeCalled()->andReturn([
                'message' => 'Uncaught',
                'type'    => 3,
            ]);
            expect($this->middleware->execOnShutdown())->toBeNull();

        });

        it('exclude error type and match', function () {

            $actual = $this->middleware->phpErrorHandler(\E_USER_DEPRECATED, 'deprecated', 'file.php', 1);
            // null means use default $handler->handle($request)
            expect($actual)->toBeNull();

            expect(\error_reporting())->toBe(\E_ALL | \E_STRICT);
            expect(\ini_get('display_errors'))->toBe("0");

        });

        it('throws ErrorException on non excluded php errors', function () {

            $closure = function () {
                 $this->middleware->phpErrorHandler(\E_WARNING, 'warning', 'file.php', 1);
            };
            expect($closure)->toThrow(new \ErrorException('warning', 0, \E_WARNING, 'file.php', 1));

        });

    });

});
