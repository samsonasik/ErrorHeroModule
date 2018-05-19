<?php

namespace ErrorHeroModule\Spec\Middleware;

use Aura\Di\Container as AuraContainer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Doctrine\ORM\EntityManager;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Middleware\Expressive;
use ErrorHeroModule\Middleware\ExpressiveFactory;
use Kahlan\Plugin\Double;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Zend\Db\Adapter\Adapter;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\ServiceManager\ServiceManager;

describe('ExpressiveFactory', function () {

    given('factory', function () {
        return new ExpressiveFactory();
    });

    given('config', function () {

        return [

            'db' => [
                'username' => 'root',
                'password' => '',
                'driver'   => 'pdo_mysql',
                'dsn'      => 'mysql:host=localhost;dbname=errorheromodule',
                'driver_options' => [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
                ],
                'adapters' => [
                    'my-adapter' => [
                        'driver' => 'pdo_mysql',
                        'dsn' => 'mysql:host=localhost;dbname=errorheromodule',
                        'username' => 'root',
                        'password' => '',
                        'driver_options' => [
                            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
                        ],
                    ],
                ],
            ],

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
                                'db'     => Adapter::class,
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

    describe('__invoke()', function () {

        it('returns Expressive Middleware instance with doctrine to zend-db conversion', function () {

            $config = $this->config;
            unset($config['db']);
            $container = Double::instance(['extends' => ServiceManager::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn($config);

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

            $actual = $this->factory($container);
            expect($actual)->toBeAnInstanceOf(Expressive::class);

        });

        it('throws RuntimeException when using Symfony Container but no "db" config', function () {

            $container = Double::instance(['extends' => SymfonyContainerBuilder::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn([]);

            allow($container)->toReceive('has')->with(EntityManager::class)->andReturn(false);

            $logging = Double::instance(['extends' => Logging::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with(Logging::class)
                                               ->andReturn($logging);

            $renderer = Double::instance(['implements' => TemplateRendererInterface::class]);
            allow($container)->toReceive('get')->with(TemplateRendererInterface::class)
                                               ->andReturn($renderer);

            $actual = function () use ($container) {
                $this->factory($container);
            };
            expect($actual)->toThrow(new RuntimeException('db config is required for build "ErrorHeroModuleLogger" service by Symfony Container'));

        });

        it('returns Expressive Middleware instance with create services first for Symfony Container and db name found in adapters', function () {

            $config = $this->config;
            $config['log']['ErrorHeroModuleLogger']['writers'][0]['options']['db'] = 'my-adapter';
            $container = Double::instance(['extends' => SymfonyContainerBuilder::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn($config);

            allow($container)->toReceive('has')->with(EntityManager::class)->andReturn(false);

            $logging = Double::instance(['extends' => Logging::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with(Logging::class)
                                               ->andReturn($logging);

            $renderer = Double::instance(['implements' => TemplateRendererInterface::class]);
            allow($container)->toReceive('get')->with(TemplateRendererInterface::class)
                                               ->andReturn($renderer);

            $actual = $this->factory($container);
            expect($actual)->toBeAnInstanceOf(Expressive::class);

        });

        it('returns Expressive Middleware instance with create services first for Symfony Container and db name not found in adapters, which means use "Zend\Db\Adapter\Adapter" name', function () {

            $container = Double::instance(['extends' => SymfonyContainerBuilder::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn($this->config);

            allow($container)->toReceive('has')->with(EntityManager::class)->andReturn(false);

            $logging = Double::instance(['extends' => Logging::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with(Logging::class)
                                               ->andReturn($logging);

            $renderer = Double::instance(['implements' => TemplateRendererInterface::class]);
            allow($container)->toReceive('get')->with(TemplateRendererInterface::class)
                                               ->andReturn($renderer);

            $actual = $this->factory($container);
            expect($actual)->toBeAnInstanceOf(Expressive::class);

        });

        it('throws RuntimeException when using Aura Container but no "db" config', function () {

            $container = Double::instance(['extends' => AuraContainer::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn([]);

            allow($container)->toReceive('has')->with(EntityManager::class)->andReturn(false);

            $logging = Double::instance(['extends' => Logging::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with(Logging::class)
                                               ->andReturn($logging);

            $renderer = Double::instance(['implements' => TemplateRendererInterface::class]);
            allow($container)->toReceive('get')->with(TemplateRendererInterface::class)
                                               ->andReturn($renderer);

            $actual = function () use ($container) {
                $this->factory($container);
            };
            expect($actual)->toThrow(new RuntimeException('db config is required for build "ErrorHeroModuleLogger" service by Aura Container'));

        });

        it('returns Expressive Middleware instance with create services first for Aura Container and db name found in adapters', function () {

            $config = $this->config;
            $config['log']['ErrorHeroModuleLogger']['writers'][0]['options']['db'] = 'my-adapter';
            $container = Double::instance(['extends' => AuraContainer::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn($config);

            allow($container)->toReceive('has')->with(EntityManager::class)->andReturn(false);

            $logging = Double::instance(['extends' => Logging::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with(Logging::class)
                                               ->andReturn($logging);

            $renderer = Double::instance(['implements' => TemplateRendererInterface::class]);
            allow($container)->toReceive('get')->with(TemplateRendererInterface::class)
                                               ->andReturn($renderer);

            $actual = $this->factory($container);
            expect($actual)->toBeAnInstanceOf(Expressive::class);

        });

        it('returns Expressive Middleware instance with create services first for Aura Container and db name not found in adapters, which means use "Zend\Db\Adapter\Adapter" name', function () {

            $container = Double::instance(['extends' => AuraContainer::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn($this->config);

            allow($container)->toReceive('has')->with(EntityManager::class)->andReturn(false);

            $logging = Double::instance(['extends' => Logging::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with(Logging::class)
                                               ->andReturn($logging);

            $renderer = Double::instance(['implements' => TemplateRendererInterface::class]);
            allow($container)->toReceive('get')->with(TemplateRendererInterface::class)
                                               ->andReturn($renderer);

            $actual = $this->factory($container);
            expect($actual)->toBeAnInstanceOf(Expressive::class);

        });

        it('returns Expressive Middleware instance without doctrine to zend-db conversion', function () {

            $container = Double::instance(['extends' => ServiceManager::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn($this->config);

            $logging = Double::instance(['extends' => Logging::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with(Logging::class)
                                               ->andReturn($logging);

            $renderer = Double::instance(['implements' => TemplateRendererInterface::class]);
            allow($container)->toReceive('get')->with(TemplateRendererInterface::class)
                                               ->andReturn($renderer);

            $actual = $this->factory($container);
            expect($actual)->toBeAnInstanceOf(Expressive::class);

        });

    });

});
