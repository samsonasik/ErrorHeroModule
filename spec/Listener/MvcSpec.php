<?php

namespace ErrorHeroModule\Spec\Listener;

use ErrorHeroModule\Listener\Mvc;
use Kahlan\Plugin\Double;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;

describe('Mvc', function () {

    beforeAll(function () {
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

        $this->listener = new Mvc($config);
    });

    describe('->attach()', function () {

        it('attach dispatch.error, render.error, and *', function () {

            $eventManager = Double::instance(['implements' => EventManagerInterface::class]);
            expect($eventManager)->toReceive('attach')->with(MvcEvent::EVENT_RENDER_ERROR, [$this->listener, 'renderError']);
            expect($eventManager)->toReceive('attach')->with(MvcEvent::EVENT_DISPATCH_ERROR, [$this->listener, 'dispatchError'], 100);
            expect($eventManager)->toReceive('attach')->with('*', [$this->listener, 'phpError']);

            $this->listener->attach($eventManager);

        });

    });

});
