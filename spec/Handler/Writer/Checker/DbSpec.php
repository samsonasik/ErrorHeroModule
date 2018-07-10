<?php

namespace ErrorHeroModule\Spec\Handler\Writer\Checker;

use ErrorHeroModule\Handler\Writer\Checker\Db;
use Kahlan\Plugin\Double;
use ReflectionProperty;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;
use Zend\Log\Writer\Db as DbWriter;

describe('Db', function () {

    beforeAll(function () {

        $this->dbWriter = Double::instance(['extends' => DbWriter::class, 'methods' => '__construct']);
        $reflectionProperty = new ReflectionProperty($this->dbWriter, 'db');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->dbWriter, Double::instance(['extends' => Adapter::class, 'methods' => '__construct']));

        $this->configLoggingSettings =  [
            'same-error-log-time-range' => 86400,
        ];
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
    });

    given('writerHandler', function () {
        return new Db(
            $this->dbWriter,
            $this->configLoggingSettings,
            $this->logWritersConfig
        );
    });

    describe('__construct', function () {

        it('instanceof '. Db::class, function () {

            $actual = $this->writerHandler;
            expect($actual)->toBeAnInstanceOf(Db::class);

        });

    });

    describe('->isExists()', function () {

        it('return false if no current data', function () {

            $sql = Double::instance(['extends' => Sql::class, 'methods' => '__construct']);
            allow(TableGateway::class)->toReceive('getSql')->andReturn($sql);

            $select = Double::instance(['extends' => Select::class, 'methods' => '__construct']);
            allow($select)->toReceive('columns')->with(['date']);
            allow($select)->toReceive('where');
            allow($select)->toReceive('order');
            allow($select)->toReceive('limit');
            allow($sql)->toReceive('select')->andReturn($select);

            $resultSet = Double::instance(['extends' => ResultSet::class, 'methods' => '__construct']);
            allow($resultSet)->toReceive('current')->andReturn(null);
            allow(TableGateway::class)->toReceive('selectWith')->with($select)->andReturn($resultSet);

            $actual = $this->writerHandler->isExists('file', 1, 'Undefined offset: 1', 'http://serverUrl/uri', 'E_NOTICE');
            expect($actual)->toBe(false);

        });

        it('return false if has current data but timestamp is expired', function () {

            $sql = Double::instance(['extends' => Sql::class, 'methods' => '__construct']);
            allow(TableGateway::class)->toReceive('getSql')->andReturn($sql);

            $select = Double::instance(['extends' => Select::class, 'methods' => '__construct']);
            allow($select)->toReceive('columns')->with(['date']);
            allow($select)->toReceive('where');
            allow($select)->toReceive('order');
            allow($select)->toReceive('limit');
            allow($sql)->toReceive('select')->andReturn($select);

            $resultSet = Double::instance(['extends' => ResultSet::class, 'methods' => '__construct']);

            $current = \date('Y-m-d');
            $date    = \date_create($current);
            \date_sub($date, \date_interval_create_from_date_string("40 days"));
            $date =  \date_format($date,"Y-m-d H:i:s");

            allow($resultSet)->toReceive('current')->andReturn(
                [
                    'date' => $date,
                ]
            );
            allow(TableGateway::class)->toReceive('selectWith')->with($select)->andReturn($resultSet);

            $actual = $this->writerHandler->isExists('file', 1, 'Undefined offset: 1', 'http://serverUrl/uri', 'E_NOTICE');
            expect($actual)->toBe(false);

        });

        it('return true if has current data but timestamp === current time', function () {

            $sql = Double::instance(['extends' => Sql::class, 'methods' => '__construct']);
            allow(TableGateway::class)->toReceive('getSql')->andReturn($sql);

            $select = Double::instance(['extends' => Select::class, 'methods' => '__construct']);
            allow($select)->toReceive('columns')->with(['date']);
            allow($select)->toReceive('where');
            allow($select)->toReceive('order');
            allow($select)->toReceive('limit');
            allow($sql)->toReceive('select')->andReturn($select);

            $resultSet = Double::instance(['extends' => ResultSet::class, 'methods' => '__construct']);

            allow($resultSet)->toReceive('current')->andReturn(
                [
                    'date' => \date('Y-m-d H:i:s'),
                ]
            );
            allow(TableGateway::class)->toReceive('selectWith')->with($select)->andReturn($resultSet);

            $actual = $this->writerHandler->isExists('file', 1, 'Undefined offset: 1', 'http://serverUrl/uri', 'E_NOTICE');
            expect($actual)->toBe(true);

        });

    });

});
