<?php

namespace ErrorHeroModule\Spec;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Doctrine\ORM\EntityManager;
use ErrorHeroModule\Listener\Mvc;
use ErrorHeroModule\Module;
use Kahlan\Plugin\Double;
use Zend\EventManager\EventManagerInterface;
use Zend\ModuleManager\Listener\ConfigListener;
use Zend\ModuleManager\ModuleEvent;
use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

describe('Module', function () {

    given('module', function() {
        return new Module();
    });

    describe('->getConfig()', function () {

        it('return "config" array', function () {

            $moduleConfig = include __DIR__.'/../config/module.config.php';

            $actual = $this->module->getConfig();
            expect($actual)->toBe($moduleConfig);

        });

    });

    describe('->init()', function () {

        it('receive ModuleManager that get Eventmanager that attach ModuleEvent::EVENT_LOAD_MODULES_POST', function () {

            $moduleManager = Double::instance(['extends' => ModuleManager::class, 'methods' => '__construct']);
            $eventManager    = Double::instance(['implements' => EventManagerInterface::class]);

            allow($moduleManager)->toReceive('getEventManager')->andReturn($eventManager);
            expect($eventManager)->toReceive('attach')->with(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this->module, 'convertDoctrineToZendDbConfig']);

            $this->module->init($moduleManager);

        });

    });

    describe('->convertDoctrineToZendDbConfig()', function () {

        it('does not has EntityManager service', function () {

            $moduleEvent = Double::instance(['extends' => ModuleEvent::class, 'methods' => '__construct']);
            $serviceManager  = Double::instance(['implements' => ServiceLocatorInterface::class]);
            allow($moduleEvent)->toReceive('getParam')->with('ServiceManager')->andReturn($serviceManager);
            allow($serviceManager)->toReceive('has')->with(EntityManager::class)->andReturn(false);

            $this->module->convertDoctrineToZendDbConfig($moduleEvent);
            expect($moduleEvent)->not->toReceive('getConfigListener');

        });

        it('has EntityManager service but already has db config', function () {

            $moduleEvent = Double::instance(['extends' => ModuleEvent::class, 'methods' => '__construct']);
            $serviceManager  = Double::instance(['implements' => ServiceLocatorInterface::class]);
            allow($moduleEvent)->toReceive('getParam')->with('ServiceManager')->andReturn($serviceManager);
            allow($serviceManager)->toReceive('has')->with(EntityManager::class)->andReturn(true);

            $configListener = Double::instance(['extends' => ConfigListener::class, 'methods' => '__construct']);
            allow($moduleEvent)->toReceive('getConfigListener')->andReturn($configListener);
            allow($configListener)->toReceive('getMergedConfig')->andReturn([
                'db' => [
                    'username' => 'root',
                    'password' => '',
                    'driver'   => 'pdo_mysql',
                    'database' => 'mydb',
                    'host'     => 'localhost',
                ],
            ]);

            $this->module->convertDoctrineToZendDbConfig($moduleEvent);
            expect($serviceManager)->not->toReceive('get')->with(EntityManager::class);

        });

        it('has EntityManager service but already does not has db config not isset driverOptions', function () {

            $moduleEvent = Double::instance(['extends' => ModuleEvent::class, 'methods' => '__construct']);
            $serviceManager  = Double::instance(['implements' => ServiceLocatorInterface::class]);
            allow($moduleEvent)->toReceive('getParam')->with('ServiceManager')->andReturn($serviceManager);
            allow($serviceManager)->toReceive('has')->with(EntityManager::class)->andReturn(true);

            $configListener = Double::instance(['extends' => ConfigListener::class, 'methods' => '__construct']);
            allow($moduleEvent)->toReceive('getConfigListener')->andReturn($configListener);
            allow($configListener)->toReceive('getMergedConfig')->andReturn([]);

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
            allow($serviceManager)->toReceive('get')->with(EntityManager::class)->andReturn($entityManager);

            $this->module->convertDoctrineToZendDbConfig($moduleEvent);
            expect($serviceManager)->toReceive('get')->with(EntityManager::class);

        });

        it('has EntityManager service but already does not has db config with isset driverOptions', function () {

            $moduleEvent = Double::instance(['extends' => ModuleEvent::class, 'methods' => '__construct']);
            $serviceManager  = Double::instance(['implements' => ServiceLocatorInterface::class]);
            allow($moduleEvent)->toReceive('getParam')->with('ServiceManager')->andReturn($serviceManager);
            allow($serviceManager)->toReceive('has')->with(EntityManager::class)->andReturn(true);

            $configListener = Double::instance(['extends' => ConfigListener::class, 'methods' => '__construct']);
            allow($moduleEvent)->toReceive('getConfigListener')->andReturn($configListener);
            allow($configListener)->toReceive('getMergedConfig')->andReturn([]);

            $entityManager = Double::instance(['extends' => EntityManager::class, 'methods' => '__construct']);
            $connection    = Double::instance(['extends' => Connection::class, 'methods' => '__construct']);

            $driver = Double::instance(['extends' => Driver::class, 'methods' => '__construct']);
            allow($driver)->toReceive('getName')->andReturn('pdo_mysql');

            allow($connection)->toReceive('getParams')->andReturn([
                'driverOptions' => [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
                ],
            ]);
            allow($connection)->toReceive('getUsername')->andReturn('root');
            allow($connection)->toReceive('getPassword')->andReturn('');
            allow($connection)->toReceive('getDriver')->andReturn($driver);
            allow($connection)->toReceive('getDatabase')->andReturn('mydb');
            allow($connection)->toReceive('getHost')->andReturn('localhost');
            allow($connection)->toReceive('getPort')->andReturn('3306');

            allow($entityManager)->toReceive('getConnection')->andReturn($connection);
            allow($serviceManager)->toReceive('get')->with(EntityManager::class)->andReturn($entityManager);

            $this->module->convertDoctrineToZendDbConfig($moduleEvent);
            expect($serviceManager)->toReceive('get')->with(EntityManager::class);

        });

    });

    describe('->onBootstrap()', function () {

        it('pull Mvc Listener and use it to attach with EventManager', function () {

            $application     = Double::instance(['extends' => Application::class, 'methods' => '__construct']);
            $mvcListener     = Double::instance(['extends' => Mvc::class, 'methods' => '__construct']);
            $serviceManager  = Double::instance(['implements' => ServiceLocatorInterface::class]);
            $eventManager    = Double::instance(['implements' => EventManagerInterface::class]);
            $mvcEvent        = Double::instance(['extends' => MvcEvent::class]);

            expect($mvcListener)->toReceive('attach')->with($eventManager);

            allow($application)->toReceive('getServiceManager')->andReturn($serviceManager);
            allow($application)->toReceive('getEventManager')->andReturn($eventManager);
            allow($serviceManager)->toReceive('get')->with(Mvc::class)->andReturn($mvcListener);
            allow($mvcEvent)->toReceive('getApplication')->andReturn($application);

            $this->module->onBootstrap($mvcEvent);

        });

    });

});
