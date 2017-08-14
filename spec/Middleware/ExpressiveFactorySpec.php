<?php

namespace ErrorHeroModule\Spec\Middleware;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Doctrine\ORM\EntityManager;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Middleware\Expressive;
use ErrorHeroModule\Middleware\ExpressiveFactory;
use Kahlan\Plugin\Double;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

describe('ExpressiveFactory', function () {

    given('factory', function () {
        return new ExpressiveFactory();
    });

    given('config', function () {

        return [

            'error-hero-module' => [
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
                        'layout' => 'layout::default',
                        'view'   => 'error-hero-module::error-default'
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
            ],

            'log' => [
                'ErrorHeroModuleLogger' => [
                    'writers' => [

                        [
                            'name' => 'db',
                            'options' => [
                                'db'     => 'Zend\Db\Adapter\Adapter',
                                'table'  => 'error_log',
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

                    ],
                ],
            ],

        ];

    });

    describe('->__invoke()', function () {

        it('returns Expressive Middleware instance with doctrine to zend-db conversion', function () {

            $container = Double::instance(['implements' => ServiceLocatorInterface::class]);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn($this->config);

            allow($container)->toReceive('has')->with(EntityManager::class)->andReturn(true);
            $entityManager = Double::instance(['extends' => EntityManager::class, 'methods' => '__construct']);
            $connection    = Double::instance(['extends' => Connection::class, 'methods' => '__construct']);

            $driver = Double::instance(['extends' => Driver::class, 'methods' => '__construct']);
            allow($driver)->toReceive('getName')->andReturn('pdo_mysql');

            allow($connection)->toReceive('getParams')->andReturn([]);
            allow($connection)->toReceive('getUsername')->andReturn('root');
            allow($connection)->toReceive('getPassword')->andReturn('');
            allow($connection)->toReceive('getDriver')->andReturn($driver);
            allow($connection)->toReceive('getDatabase')->andReturn('mydb');
            allow($connection)->toReceive('getHost')->andReturn('localhost');
            allow($connection)->toReceive('getPort')->andReturn('3306');

            allow($entityManager)->toReceive('getConnection')->andReturn($connection);
            allow($container)->toReceive('get')->with(EntityManager::class)->andReturn(
                $entityManager
            );

            $logging = Double::instance(['extends' => Logging::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with(Logging::class)
                                               ->andReturn($logging);

            $renderer = Double::instance(['implements' => TemplateRendererInterface::class]);
            allow($container)->toReceive('get')->with(TemplateRendererInterface::class)
                                               ->andReturn($renderer);

            $actual = $this->factory->__invoke($container);
            expect($actual)->toBeAnInstanceOf(Expressive::class);

        });

        it('returns Expressive Middleware instance without doctrine to zend-db conversion', function () {

            $container = Double::instance(['implements' => ServiceLocatorInterface::class]);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn($this->config);

            $logging = Double::instance(['extends' => Logging::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with(Logging::class)
                                               ->andReturn($logging);

            $renderer = Double::instance(['implements' => TemplateRendererInterface::class]);
            allow($container)->toReceive('get')->with(TemplateRendererInterface::class)
                                               ->andReturn($renderer);

            $actual = $this->factory->__invoke($container);
            expect($actual)->toBeAnInstanceOf(Expressive::class);

        });

    });

});
