<?php

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\InMemory;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [

    'service_manager' => [
        'factories' => [
            Message::class => InvokableFactory::class,
            InMemory::class    => InvokableFactory::class,
        ],
    ],

    'db' => [
        'username' => 'root',
        'password' => 'wrong_password',
        'driver' => 'Pdo',
        'dsn' => 'mysql:dbname=errorheromodule;host=127.0.0.1',
        'driver_options' => [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
        ],
    ],

    'log' => [
        'ErrorHeroModuleLogger' => [
            'writers' => [

                [
                    'name' => 'db',
                    'options' => [
                        'db'     => AdapterInterface::class,
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
            'enable' => true,

            // Laminas\Mail\Message instance registered at service manager
            'mail-message'   => Message::class,

            // Laminas\Mail\Transport\TransportInterface instance registered at service manager
            'mail-transport' => InMemory::class,

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
];
