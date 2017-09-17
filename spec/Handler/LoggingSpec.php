<?php

namespace ErrorHeroModule\Spec\Handler;

use ErrorHeroModule\Handler\Logging;
use Kahlan\Plugin\Double;
use ReflectionMethod;
use ReflectionProperty;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;
use Zend\Diactoros\ServerRequest;
use Zend\Http\PhpEnvironment\Request;
use Zend\Log\Logger;
use Zend\Log\Writer\Db as DbWriter;
use Zend\Mail\Message;

describe('LoggingSpec', function () {

    beforeAll(function () {
        $this->logger = Double::instance(['extends' => Logger::class]);
        $this->serverUrl = 'http://serverUrl';
        $this->requestUri = '/';
        $this->request   = Double::instance(['extends' => Request::class, 'methods' => '__construct']);
        $this->errorHeroModuleLocalConfig = [
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
        $this->logWritersConfig = [

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

        $this->dbWriter = Double::instance(['extends' => DbWriter::class, 'methods' => '__construct']);
        $reflectionProperty = new ReflectionProperty($this->dbWriter, 'db');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->dbWriter, Double::instance(['extends' => 'Zend\Db\Adapter\Adapter', 'methods' => '__construct']));

        $this->logger->addWriter($this->dbWriter);
    });

    given('logging', function ()  {
        return new Logging(
            $this->logger,
            $this->serverUrl,
            $this->request,
            $this->requestUri,
            $this->errorHeroModuleLocalConfig,
            $this->logWritersConfig,
            null,
            null
        );
    });

    describe('->setServerRequestandRequestUri()', function () {

        it('set request and requestUri properties', function () {

            $this->logging->setServerRequestandRequestUri(new ServerRequest([], [], $this->serverUrl  . '/error-preview', 'GET'));

            $r = new ReflectionProperty($this->logging, 'request');
            $r->setAccessible(true);
            expect($r->getValue($this->logging))->toBeAnInstanceOf(ServerRequest::class);

            $r2 = new ReflectionProperty($this->logging, 'requestUri');
            $r2->setAccessible(true);
            expect($r2->getValue($this->logging))->toBe('/error-preview');

            $r3 = new ReflectionMethod($this->logging, 'getRequestData');
            $r3->setAccessible(true);
            $r3->invoke($this->logging);

        });

    });

    describe('->handleErrorException()', function ()  {

        it('not log if exists', function ()  {

            $sql = Double::instance(['extends' => Sql::class, 'methods' => '__construct']);
            allow(TableGateway::class)->toReceive('getSql')->andReturn($sql);

            $select = Double::instance(['extends' => Select::class, 'methods' => '__construct']);
            allow($select)->toReceive('where');
            allow($select)->toReceive('order');
            allow($select)->toReceive('limit');
            allow($sql)->toReceive('select')->andReturn($select);

            $resultSet = Double::instance(['extends' => ResultSet::class, 'methods' => '__construct']);

            allow($resultSet)->toReceive('count')->andReturn(1);
            allow($resultSet)->toReceive('current')->andReturn(
                [
                    'date' => date('Y-m-d H:i:s'),
                ]
            );
            allow(TableGateway::class)->toReceive('selectWith')->with($select)->andReturn($resultSet);

            expect($this->logger)->not->toReceive('log');

            $exception = new \Exception();
            $this->logging->handleErrorException($exception);

        });

        it('not log if exists and exception instanceof ErrorException', function ()  {

            $sql = Double::instance(['extends' => Sql::class, 'methods' => '__construct']);
            allow(TableGateway::class)->toReceive('getSql')->andReturn($sql);

            $select = Double::instance(['extends' => Select::class, 'methods' => '__construct']);
            allow($select)->toReceive('where');
            allow($select)->toReceive('order');
            allow($select)->toReceive('limit');
            allow($sql)->toReceive('select')->andReturn($select);

            $resultSet = Double::instance(['extends' => ResultSet::class, 'methods' => '__construct']);

            allow($resultSet)->toReceive('current')->andReturn(
                [
                    'date' => date('Y-m-d H:i:s'),
                ]
            );
            allow($resultSet)->toReceive('count')->andReturn(1);
            allow(TableGateway::class)->toReceive('selectWith')->with($select)->andReturn($resultSet);

            expect($this->logger)->not->toReceive('log');

            $exception = new \ErrorException();
            $this->logging->handleErrorException($exception);

        });

    });

});
