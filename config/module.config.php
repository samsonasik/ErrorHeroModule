<?php

namespace ErrorHeroModule;

use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\Log;
use Zend\Mvc\Controller\AbstractConsoleController as ZF2AbstractConsoleController;

return [

    'controllers' => [
        'invokables' => [
            // sm v2 compat
            Controller\ErrorPreviewController::class           => Controller\ErrorPreviewController::class,
            Controller\ErrorPreviewConsoleZF2Controller::class => Controller\ErrorPreviewConsoleZF2Controller::class,
            Controller\ErrorPreviewConsoleZF3Controller::class => Controller\ErrorPreviewConsoleZF3Controller::class,
        ],
        'factories' => [
            // sm v3
            Controller\ErrorPreviewController::class           => InvokableFactory::class,
            Controller\ErrorPreviewConsoleZF2Controller::class => InvokableFactory::class,
            Controller\ErrorPreviewConsoleZF3Controller::class => InvokableFactory::class,
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
                        'action'     => 'exception',
                    ],
                ],
            ],

        ],
    ],

    'console' => [
        'router' => [
            'routes' => [
                'error-preview-console' => [
                    'options' => [
                        'route'    => 'error-preview [<action>]',
                        'defaults' => [
                            'controller' => (class_exists(ZF2AbstractConsoleController::class) ? Controller\ErrorPreviewConsoleZF2Controller::class : Controller\ErrorPreviewConsoleZF3Controller::class,
                            'action'     => 'exception'
                        ],
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
            Listener\Mvc::class    => Listener\MvcFactory::class,
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
