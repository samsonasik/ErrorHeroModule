<?php

namespace ErrorHeroModule;

use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\Log;

return [

    'controllers' => [
        'invokables' => [
            // sm v2 compat
            Controller\ErrorPreviewController::class => Controller\ErrorPreviewController::class,
        ],
        'factories' => [
            // sm v3
            Controller\ErrorPreviewController::class => InvokableFactory::class,
        ],
    ],

    'router' => [
        'routes' => [
            'error-preview' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/error-preview[/][:action]',
                    'defaults' => [
                        'controller' => Controller\ErrorPreviewController::class,
                        'action' => 'exception',
                    ],
                ],
            ],
        ],
    ],

    'service_manager' => [
        'abstract_factories' => [
            Log\LoggerAbstractServiceFactory::class,
        ],
        'factories' => [
            Listener\Mvc::class => Listener\MvcFactory::class,
            Handler\Logging::class => Handler\LoggingFactory::class,
        ],
    ],

    'listeners' => [
        Listener\Mvc::class,
    ],

    'view_manager' => [
        'template_map' => [
           'error-hero-module/error-default' => __DIR__.'/../view/error-hero-module/error-default.phtml',
       ],
    ],

];
