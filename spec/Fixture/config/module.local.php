<?php

namespace ErrorHeroModule;

use Laminas\Log;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [

    'controllers' => [
        'factories' => [
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
       'template_path_stack' => [
           __DIR__.'/../view',
           __DIR__.'/../../../view',
       ],
    ],

];
