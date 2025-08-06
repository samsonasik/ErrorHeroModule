<?php

namespace ErrorHeroModule\Spec\Handler;

use ErrorHeroModule\Compat\Logger;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Handler\LoggingFactory;
use Kahlan\Plugin\Double;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

describe('LoggingFactorySpec', function (): void {

    given('factory', fn() : LoggingFactory => new LoggingFactory());

    given('config', fn() : array => [

        'error-hero-module' => [
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

                'mail-dsn' => 'smtp://localhost:25',

                // email sender
                'email-from'    => 'Sender Name <sender@host.com>',

                // to include or not $_FILES on send mail
                'include-files-to-attachments' => true,

                'email-to-send' => [
                    'developer1@foo.com',
                    'developer2@foo.com',
                ],
            ],
        ],
    ]);

    describe('__invoke()', function (): void {

        it('instance of Logging on non-console with container does not has "Request" service', function (): void {

            $config = $this->config;

            $container = Double::instance(['implements' => ContainerInterface::class]);
            allow($container)->toReceive('get')->with('config')
                                                ->andReturn($config);

            $logger = Double::instance(['extends' => LoggerInterface::class]);
            allow($container)->toReceive('get')->with('ErrorHeroModuleLogger')
                                                ->andReturn($logger);

            $actual = $this->factory($container);
            expect($actual)->toBeAnInstanceOf(Logging::class);

        });

        it('instance of Logging without bring Laminas\Mail\Message and Laminas\Mail\Transport if email-notification-settings is disabled', function (): void {

            $config = $this->config;

            $container = Double::instance(['implements' => ContainerInterface::class]);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn($config);

            $logger = Double::instance(['extends' => LoggerInterface::class]);
            allow($container)->toReceive('get')->with('ErrorHeroModuleLogger')
                                               ->andReturn($logger);

            $actual = $this->factory($container);
            expect($actual)->toBeAnInstanceOf(Logging::class);

        });

        it('throw RuntimeException if Logging try bring non-existence Laminas\Mail\Message service while email-notification-settings is enabled', function (): void {

            $config = $this->config;
            $config['error-hero-module']['email-notification-settings']['enable'] = true;

            $container = Double::instance(['implements' => ContainerInterface::class]);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn($config);

            $logger = Double::instance(['extends' => LoggerInterface::class]);
            allow($container)->toReceive('get')->with('ErrorHeroModuleLogger')
                                               ->andReturn($logger);

            $closure = function () use ($container): void {
                $this->factory($container);
            };

            expect($closure)->toThrow(new RuntimeException());

        });

        it('throw RuntimeException if Logging try bring non-existence Laminas\Mail\Transport\TransportInterface service while email-notification-settings is enabled', function (): void {

            $config = $this->config;
            $config['error-hero-module']['email-notification-settings']['enable'] = true;

            $container = Double::instance(['implements' => ContainerInterface::class]);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn($config);

            $logger = Double::instance(['extends' => LoggerInterface::class]);
            allow($container)->toReceive('get')->with('ErrorHeroModuleLogger')
                                               ->andReturn($logger);

            $closure = function () use ($container): void {
                $this->factory($container);
            };

            expect($closure)->toThrow(new RuntimeException());

        });

        it('instance of Logging with bring Laminas\Mail\Message and Laminas\Mail\Transport if email-notification-settings is enabled and both services exist', function (): void {

            $config = $this->config;
            $config['error-hero-module']['email-notification-settings']['enable'] = true;

            $container = Double::instance(['implements' => ContainerInterface::class]);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn($config);

            $logger = Double::instance(['extends' => LoggerInterface::class]);
            allow($container)->toReceive('get')->with('ErrorHeroModuleLogger')
                                               ->andReturn($logger);

            $actual = $this->factory($container);
            expect($actual)->toBeAnInstanceOf(Logging::class);

        });

    });

});
