<?php

namespace ErrorHeroModule\Spec\Listener;

use ErrorHeroModule\Listener\Mvc;
use ErrorHeroModule\Listener\MvcFactory;
use Kahlan\Plugin\Double;
use Zend\ServiceManager\ServiceManager;

describe('MvcFactory', function () {

    beforeAll(function () {
        $this->factory = new MvcFactory();
    });

    describe('->__invoke()', function () {

        it('return Mvc Listener instance', function () {

            $config = [
                'error-hero-module' => [
                    'enable' => true,
                    'options' => [

                        // excluded php errors
                        'exclude-php-errors' => [
                            E_USER_DEPRECATED
                        ],

                        // show or not error
                        'display_errors'  => 1,

                        // if enable and display_errors = 0
                        'view_errors' => 'error-hero-module/error-default'
                    ],
                    'logging' => [
                        'range-same-error' => 86400, // 1 day 1 same error will be logged
                        'adapters' => [
                            'stream' => [
                                'path' => '/var/log'
                            ],
                            'db' => [
                                'zend-db-adapter' => 'Zend\Db\Adapter\Adapter',
                                'table'           => 'log'
                            ],
                        ],
                    ],
                    'email-notification' => [
                        'developer1@foo.com',
                        'developer2@foo.com',
                    ],
                ],
            ];

            $container = Double::instance(['extends' => ServiceManager::class]);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn($config);

            $actual = $this->factory->__invoke($container);
            expect($actual)->toBeAnInstanceOf(Mvc::class);

        });

    });

});
