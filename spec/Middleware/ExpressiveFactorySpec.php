<?php

namespace ErrorHeroModule\Spec\Middleware;

use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Middleware\Expressive;
use ErrorHeroModule\Middleware\ExpressiveFactory;
use Kahlan\Plugin\Double;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

describe('ExpressiveFactory', function () {

    given('factory', function () {
        return new ExpressiveFactory();
    });

    describe('->__invoke()', function () {

        it('return Expressive Middleware instance', function () {

            $config = [
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
                            'layout' => 'layout/layout',
                            'view'   => 'error-hero-module/error-default'
                        ],

                        // if enable and display_errors = 0, the console will bring message
                        'console' => [
                            'message' => 'We have encountered a problem and we can not fulfill your request. An error report has been generated and send to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience.',
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
            ];

            $container = Double::instance(['implements' => ServiceLocatorInterface::class]);
            allow($container)->toReceive('get')->with('config')
                                               ->andReturn($config);

            $logging = Double::instance(['extends' => Logging::class, 'methods' => '__construct']);
            allow($container)->toReceive('get')->with(Logging::class)
                                               ->andReturn($logging);

            $renderer = Double::implements(['extends' => TemplateRendererInterface::class]);
            allow($container)->toReceive('get')->with(TemplateRendererInterface::class)
                                               ->andReturn($renderer);

            $actual = $this->factory->__invoke($container);
            expect($actual)->toBeAnInstanceOf(Expressive::class);

        });

    });

});
