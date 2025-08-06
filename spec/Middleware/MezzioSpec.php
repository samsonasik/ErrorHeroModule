<?php

namespace ErrorHeroModule\Spec\Middleware;

use Closure;
use ErrorException;
use ErrorHeroModule\Compat\Logger;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Middleware\Mezzio;
use Exception;
use Kahlan\Plugin\Double;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\Log\Writer\Db;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\AggregateResolver;
use Laminas\View\Resolver\TemplateMapResolver;
use Mezzio\LaminasView\LaminasViewRenderer;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

describe('Mezzio', function (): void {

    given('logging', fn() : object => Double::instance([
        'extends' => Logging::class,
        'methods' => '__construct'
    ]));

    given('renderer', function (): LaminasViewRenderer {

        $renderer = new PhpRenderer();
        $resolver = new AggregateResolver();

        $map = new TemplateMapResolver([
            'layout/layout'                   => __DIR__ . '/../Fixture/view/layout/layout.phtml',
            'error-hero-module/error-default' => __DIR__ . '/../../view/error-hero-module/error-default.phtml',
        ]);
        $resolver->attach($map);
        $renderer->setResolver($resolver);

        return new LaminasViewRenderer($renderer);

    });

    given('logger', function (): LoggerInterface {
        return new \Monolog\Logger('error-hero-module');
    });

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

            // for Mezzio, when container doesn't has \Mezzio\Template\TemplateRendererInterface service
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

            // DSN for mailer
            'mail-dsn' => 'smtp://localhost:25',

            // email sender
            'email-from'    => 'Sender Name <sender@host.com>',

            'email-to-send' => [
                'developer1@foo.com',
                'developer2@foo.com',
            ],
        ],
    ]);

    given('request', fn() : ServerRequest => new ServerRequest(
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
    ));

    given('middleware', fn() : Mezzio => new Mezzio(
        $this->config,
        $this->logging,
        $this->renderer
    ));

    describe('->process()', function (): void {

        it('returns handle() when not enabled', function (): void {

            $config = [];
            $config['enable'] = false;
            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn(new Response());
            $middleware = new Mezzio($config, $this->logging, $this->renderer);

            $response = $middleware->process($this->request, $handler);
            expect($response)->toBeAnInstanceOf(ResponseInterface::class);

        });

        it('returns handle() when no error', function (): void {

            $handler  = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn(new Response());

            allow(Logging::class)->toReceive('setServerRequestandRequestUri')->with($this->request);

            $actual = $this->middleware->process($this->request, $handler);
            expect($actual)->toBeAnInstanceOf(ResponseInterface::class);

        });

        context('error', function (): void {

            it('non-xmlhttprequest: returns error page on display_errors = 0', function (): void {

                $config = $this->config;
                $config['display-settings']['display_errors'] = 0;

                $logging = new Logging(
                    $this->logger,
                    true
                );

                $handler  = Double::instance(['implements' => RequestHandlerInterface::class]);
                allow($handler)->toReceive('handle')->with($this->request)->andRun(function (): never {
                    throw new Exception('message');
                });
                $middleware = new Mezzio($config, $logging, $this->renderer);

                $response = $middleware->process($this->request, $handler);
                expect($response)->toBeAnInstanceOf(Response::class);

                $content = $response->getBody()->__toString();
                expect($content)->toContain('<title>Error');
                expect($content)->toContain('<p>We have encountered a problem and we can not fulfill your request');

            });

            it('non-xmlhttprequest: shows error on display_errors = 1', function (): void {

                $config = $this->config;
                $config['display-settings']['display_errors'] = 1;

                $logging = new Logging(
                    $this->logger,
                    true
                );

                $handler  = Double::instance(['implements' => RequestHandlerInterface::class]);
                allow($handler)->toReceive('handle')->with($this->request)->andRun(function (): never {
                    throw new Exception('message');
                });
                $middleware = new Mezzio($config, $logging, $this->renderer);

                $closure = function () use ($middleware, $handler): void {
                    $middleware->process($this->request, $handler);
                };
                expect($closure)->toThrow(new Exception('message'));

            });

            it('passed renderer is null returns error message on display_errors = 0', function (): void {

                $config = $this->config;
                $config['display-settings']['display_errors'] = 0;

                $logging = new Logging(
                    $this->logger,
                    true
                );

                $request = $this->request;
                $request  = $request->withHeader('X-Requested-With', 'XmlHttpRequest');

                $handler  = Double::instance(['implements' => RequestHandlerInterface::class]);
                allow($handler)->toReceive('handle')->with($request)->andRun(function (): never {
                    throw new Exception('message');
                });
                $middleware = new Mezzio($config, $logging, null);

                $response = $middleware->process($request, $handler);
                expect($response)->toBeAnInstanceOf(Response::class);
                expect($response->getBody()->__toString())->toBe(<<<json
{
    "type": "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",
    "title": "Internal Server Error",
    "status": 500,
    "detail": "We have encountered a problem and we can not fulfill your request. An error report has been generated and sent to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience."
}
json
                );

            });

            it('xmlhttprequest: returns error page on display_errors = 0', function (): void {

                $config = $this->config;
                $config['display-settings']['display_errors'] = 0;

                $logging = new Logging(
                    $this->logger,
                    true
                );

                $request  = $this->request;
                $request  = $request->withHeader('X-Requested-With', 'XmlHttpRequest');

                $handler  = Double::instance(['implements' => RequestHandlerInterface::class]);
                allow($handler)->toReceive('handle')->with($request)->andRun(function (): never {
                    throw new Exception('message');
                });
                $middleware = new Mezzio($config, $logging, $this->renderer);

                $response = $middleware->process($request, $handler);
                expect($response)->toBeAnInstanceOf(Response::class);
                expect($response->getBody()->__toString())->toBe(<<<json
{
    "type": "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",
    "title": "Internal Server Error",
    "status": 500,
    "detail": "We have encountered a problem and we can not fulfill your request. An error report has been generated and sent to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience."
}
json
                );

            });

            it('xmlhttprequest: shows error on display_errors = 1', function (): void {

                $config = $this->config;
                $config['display-settings']['display_errors'] = 1;

                $logging = new Logging(
                    $this->logger,
                    true
                );

                $request  = $this->request;
                $request  = $request->withHeader('X-Requested-With', 'XmlHttpRequest');

                $handler  = Double::instance(['implements' => RequestHandlerInterface::class]);
                allow($handler)->toReceive('handle')->with($request)->andRun(function (): never {
                    throw new Exception('message');
                });
                $middleware = new Mezzio($config, $logging, $this->renderer);

                $closure = function () use ($middleware, $request, $handler): void {
                    $middleware->process($request, $handler);
                };
                expect($closure)->toThrow(new Exception('message'));

            });

        });

        it('do not call logging->handleErrorException() if $e->getParam("exception") and has excluded exception match', function (): void {

            $config = $this->config;
            $config['display-settings']['exclude-exceptions'] = [
                Exception::class
            ];
            $exception = new Exception('message');

            $logging = new Logging(
                $this->logger,
                true
            );

            $request  = $this->request;
            $request  = $request->withHeader('X-Requested-With', 'XmlHttpRequest');

            $handler  = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($request)->andRun(function () use ($exception): never {
                throw $exception;
            });
            $middleware = new Mezzio($config, $logging, $this->renderer);
            $closure = function () use ($middleware, $request, $handler): void {
                $middleware->process($request, $handler);
            };
            expect($closure)->toThrow($exception);
            expect($logging)->not->toReceive('handleErrorException');

        });

    });

    describe('->phpFatalErrorHandler()', function (): void  {

        it('returns buffer on no error', function (): void {

            allow('error_get_last')->toBeCalled()->andReturn(null);
            expect($this->middleware->phpFatalErrorHandler('root'))->toBe('root');

        });

        it('returns buffer on error has "Uncaught" prefix', function (): void {

            allow('error_get_last')->toBeCalled()->andReturn([
                'message' => 'Uncaught',
                'type'    => 3,
            ]);
            expect($this->middleware->phpFatalErrorHandler('Uncaught'))->toBe('Uncaught');

        });

        it('returns result property value on error not has "Uncaught" prefix and result has value', function (): void {

            allow('error_get_last')->toBeCalled()->andReturn([
                'message' => 'Fatal',
            ]);

            $middleware = & $this->middleware;
            $result = & Closure::bind(fn&($middleware) => $middleware->result, null, $middleware)($middleware);
            $result = 'Fatal error';

            expect($this->middleware->phpFatalErrorHandler('Fatal'))->toBe('Fatal error');

        });

    });

    describe('->execOnShutdown()', function (): void  {

        it('call error_get_last() and return nothing', function (): void {

            allow('error_get_last')->toBeCalled()->andReturn(null);
            expect($this->middleware->execOnShutdown())->toBeNull();

        });

        it('call error_get_last() and property_exists() after null check passed and throws', function (): void {

            allow('error_get_last')->toBeCalled()->andReturn([
                'type' => 3,
                'message' => 'class@anonymous cannot implement stdClass - it is not an interface',
                'file' => '/var/www/zf/templates/app/home-page.phtml',
                'line' => 2
            ]);

            $logger = new \Monolog\Logger('error-hero-module');

            $logging = new Logging(
                $logger,
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
                    'mail-dsn' => 'smtp://localhost:25',
                    'email-from'    => 'Sender Name <sender@host.com>',
                    'email-to-send' => [
                        'developer1@foo.com',
                        'developer2@foo.com',
                    ],
                ],
            ];

            $middleware = new Mezzio(
                $errorHeroModuleLocalConfig,
                $logging,
                $this->renderer
            );

            allow('property_exists')->toBeCalled()->with($middleware, 'request')->andReturn(true);
            allow('property_exists')->toBeCalled()->with($middleware, 'mvcEvent')->andReturn(false);

            $request = & Closure::bind(fn&($middleware) => $middleware->request, null, $middleware)($middleware);
            $request = $this->request;

            $closure = function () use ($middleware): void {
                $middleware->execOnShutdown();
            };
            expect($closure)->toThrow(new ErrorException(
                'class@anonymous cannot implement stdClass - it is not an interface'
            ));

        });

        it('call error_get_last() and property_exists() after null check passed', function (): void {

            allow('error_get_last')->toBeCalled()->andReturn([
                'type' => 3,
                'message' => 'class@anonymous cannot implement stdClass - it is not an interface',
                'file' => '/var/www/zf/templates/app/home-page.phtml',
                'line' => 2
            ]);

            $logger = new \Monolog\Logger('error-hero-module');

            $logging = new Logging(
                $logger,
                true
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
                    'mail-dsn' => 'smtp://localhost:25',
                    'email-from'    => 'Sender Name <sender@host.com>',
                    'email-to-send' => [
                        'developer1@foo.com',
                        'developer2@foo.com',
                    ],
                ],
            ];

            $middleware = new Mezzio(
                $errorHeroModuleLocalConfig,
                $logging,
                $this->renderer
            );

            allow('property_exists')->toBeCalled()->with($middleware, 'request')->andReturn(true);
            allow('property_exists')->toBeCalled()->with($middleware, 'mvcEvent')->andReturn(false);

            $request = & Closure::bind(fn&($middleware) => $middleware->request, null, $middleware)($middleware);
            $request = $this->request;

            expect($middleware->execOnShutdown())->toBeNull();

        });


    });

    describe('->phpErrorHandler()', function (): void {

        it('error_reporting() returns 0', function (): void {

            allow('error_reporting')->tobeCalled()->andReturn(0);
            $actual = $this->middleware->phpErrorHandler(2, 'mkdir(): File exists', 'file.php', 6);
            // null means use default $handler->handle($request)
            expect($actual)->toBeNull();

        });

        it('call error_get_last() and return nothing on result with "Uncaught" prefix', function (): void {

            allow('error_get_last')->toBeCalled()->andReturn([
                'message' => 'Uncaught',
                'type'    => 3,
            ]);
            expect($this->middleware->execOnShutdown())->toBeNull();

        });

        it('exclude error type and match', function (): void {

            $actual = $this->middleware->phpErrorHandler(\E_USER_DEPRECATED, 'deprecated', 'file.php', 1);
            // null means use default $handler->handle($request)
            expect($actual)->toBeNull();

            expect(\error_reporting())->toBe(\E_ALL | 2048);
            expect(\ini_get('display_errors'))->toBe("0");

        });

        it('throws ErrorException on non excluded php errors', function (): void {

            $closure = function (): void {
                 $this->middleware->phpErrorHandler(\E_WARNING, 'warning', 'file.php', 1);
            };
            expect($closure)->toThrow(new ErrorException('warning', 0, \E_WARNING, 'file.php', 1));

        });

    });

});
