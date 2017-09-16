<?php

namespace ErrorHeroModule;

use Zend\Expressive\ZendView\HelperPluginManagerFactory;
use Zend\Log;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'db' => [
        'username' => 'root',
        'password' => '',
        'driver' => 'Pdo',
        'dsn' => 'mysql:dbname=errorheromodule;host=127.0.0.1',
        'driver_options' => [
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
        ],
    ],

    'log' => [
        'ErrorHeroModuleLogger' => [
            'writers' => [

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

            ],
        ],
    ],

    'error-hero-module' => [
        // it's for the enable/disable the logger functionality
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
                'layout' => 'layout::expressive-layout',
                'view'   => 'error-hero-module::error-default'
            ],

            // if enable and display_errors = 0, and on console env, the console will bring message
            'console' => [
                'message' => 'We have encountered a problem and we can not fulfill your request. An error report has been generated and sent to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience.',
            ],

            // if enable, display_errors = 0, and request XMLHttpRequest
            // on this case, the "template" key will be ignored.
            'ajax' => [
                'message' => <<<json
{
    "type": "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",
    "title": "Internal Server Error",
    "status": 500,
    "detail": "We have encountered a problem and we can not fulfill your request. An error report has been generated and sent to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience."
}
json
            ],

        ],
        'logging-settings' => [
            // time range for same error, file, line, url, message to be re-logged
            // in seconds range, 86400 means 1 day
            'same-error-log-time-range' => 1,
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

    'dependencies' => [
        'abstract_factories' => [
            Log\LoggerAbstractServiceFactory::class,
        ],
        'factories' => [
            Middleware\Expressive::class => Middleware\ExpressiveFactory::class,
            ErrorHeroModule\Middleware\Routed\Preview\ErrorPreviewAction::class => InvokableFactory::class,

            Handler\Logging::class => Handler\LoggingFactory::class,
            'ViewHelperManager' => HelperPluginManagerFactory::class,

            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory',
        ],
    ],

    'templates' =>[
        'paths' => [
            'error-hero-module'    => [
                realpath( dirname(dirname(__DIR__) ) . '/view/error-hero-module' ),
            ],
            'layout' => [
                realpath( dirname(dirname(__DIR__) ) . '/view/layout' )
            ],
        ],
    ],

    'middleware_pipeline' => [
        'always' => [
            'middleware' => [
                Middleware\Expressive::class
            ],
            'priority' => PHP_INT_MAX,
        ],
    ],

    'routes' => [
        [
            'name' => 'error-preview',
            'path' => '/error-preview[/:action]',
            'middleware' => Middleware\Routed\Preview\ErrorPreviewAction::class,
            'allowed_methods' => ['GET'],
        ],
    ],

];
