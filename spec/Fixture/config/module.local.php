<?php

namespace ErrorHeroModule;

use ErrorHeroModule\Controller\ErrorPreviewController;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Handler\LoggingFactory;
use ErrorHeroModule\Listener\Mvc;
use ErrorHeroModule\Listener\MvcFactory;
use Laminas\Log\LoggerAbstractServiceFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [

    'controllers' => [
        'factories' => [
            ErrorPreviewController::class => InvokableFactory::class,
        ],
    ],

    'router' => [
        'routes' => [
            'error-preview' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/error-preview[/][:action]',
                    'defaults' => [
                        'controller' => ErrorPreviewController::class,
                        'action' => 'exception',
                    ],
                ],
            ],
        ],
    ],

    'service_manager' => [
        'abstract_factories' => [
            LoggerAbstractServiceFactory::class,
        ],
        'factories' => [
            Mvc::class => MvcFactory::class,
            Logging::class => LoggingFactory::class,
        ],
    ],

    'listeners' => [
        Mvc::class,
    ],

    'view_manager' => [
       'template_path_stack' => [
           __DIR__.'/../view',
           __DIR__.'/../../../view',
       ],
    ],

];
