<?php

namespace ErrorHeroModule\Spec\Middleware;

use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Middleware\Expressive;
use Kahlan\Plugin\Double;
use Zend\Db\Adapter\Adapter;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
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
            'error-hero-module/error-default' => __DIR__ . '/../Fixture/view/error-hero-module/error-default.phtml',
        ]);
        $resolver->attach($map);
        $renderer->setResolver($resolver);

        return new ZendViewRenderer($renderer);

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

        context('error', function () {

            it('non-xmlhttprequest: returns error page on display_errors = 0', function () {

                $config = $this->config;
                $config['display-settings']['display_errors'] = 0;

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
                    null,
                    '/',
                    $config,
                    $logWritersConfig,
                    null,
                    null
                );

                $request  = new ServerRequest();
                $response = new Response();
                $next     = function ($request, $response) {
                    throw new \Exception('message');
                };
                $middleware = new Expressive($config, $logging, $this->renderer);

                $actual = $middleware->__invoke($request, $response, $next);
                expect($actual)->toBeAnInstanceOf(Response::class);
                expect($actual->getBody()->__toString())->toContain('<p>We have encountered a problem and we can not fulfill your request');

            });

            it('non-xmlhttprequest: shows error on display_errors = 1', function () {

                $config = $this->config;
                $config['display-settings']['display_errors'] = 1;

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
                    null,
                    '/',
                    $config,
                    $logWritersConfig,
                    null,
                    null
                );

                $request  = new ServerRequest();
                $response = new Response();
                $next     = function ($request, $response) {
                    throw new \Exception('message');
                };
                $middleware = new Expressive($config, $logging, $this->renderer);

                $closure = function () use ($middleware, $request, $response, $next) {
                    $middleware->__invoke($request, $response, $next);
                };
                expect($closure)->toThrow(new \Exception('message'));

            });

            it('xmlhttprequest: returns error page on display_errors = 0', function () {

                $config = $this->config;
                $config['display-settings']['display_errors'] = 0;

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
                    null,
                    '/',
                    $config,
                    $logWritersConfig,
                    null,
                    null
                );

                $request  = new ServerRequest();
                $request  = $request->withHeader('X-Requested-With', 'XmlHttpRequest');
                $response = new Response();
                $next     = function ($request, $response) {
                    throw new \Exception('message');
                };
                $middleware = new Expressive($config, $logging, $this->renderer);

                $actual = $middleware->__invoke($request, $response, $next);
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

            it('non-xmlhttprequest: shows error on display_errors = 1', function () {

                $config = $this->config;
                $config['display-settings']['display_errors'] = 1;

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
                    null,
                    '/',
                    $config,
                    $logWritersConfig,
                    null,
                    null
                );

                $request  = new ServerRequest();
                $request  = $request->withHeader('X-Requested-With', 'XmlHttpRequest');
                $response = new Response();
                $next     = function ($request, $response) {
                    throw new \Exception('message');
                };
                $middleware = new Expressive($config, $logging, $this->renderer);

                $closure = function () use ($middleware, $request, $response, $next) {
                    $middleware->__invoke($request, $response, $next);
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
                null,
                '/',
                $config,
                $logWritersConfig,
                null,
                null
            );

            $request  = new ServerRequest();
            $request  = $request->withHeader('X-Requested-With', 'XmlHttpRequest');
            $response = new Response();
            $next     = function ($request, $response) use ($exception) {
                throw $exception;
            };
            $middleware = new Expressive($config, $logging, $this->renderer);
            $closure = function () use ($middleware, $request, $response, $next) {
                $middleware->__invoke($request, $response, $next);
            };
            expect($closure)->toThrow(new \Exception('message'));
            expect($logging)->not->toReceive('handleErrorException');

        });

    });

    describe('->phpErrorHandler()', function () {

        it('do not has error', function () {

            $actual = $this->middleware->phpErrorHandler(0, '', '', 0);
            // null means use default next(req, resp)
            expect($actual)->toBeNull();

        });

        it('exclude error type and match', function () {

            $actual = $this->middleware->phpErrorHandler(E_USER_DEPRECATED, 'deprecated', 'file.php', 1);
            // null means use default next(req, resp)
            expect($actual)->toBeNull();

            expect(error_reporting())->toBe(E_ALL | E_STRICT);
            expect(ini_get('display_errors'))->toBe("0");

        });

    });

});
