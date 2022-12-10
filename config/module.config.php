<?php

namespace ErrorHeroModule;

use ErrorHeroModule\Command\BaseLoggingCommand;
use ErrorHeroModule\Controller\ErrorPreviewController;
use ErrorHeroModule\Controller\ErrorPreviewConsoleController;
use Laminas\Log\LoggerAbstractServiceFactory;
use ErrorHeroModule\Listener\Mvc;
use ErrorHeroModule\Listener\MvcFactory;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Handler\LoggingFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [

    'controllers' => [
        'factories' => [
            ErrorPreviewController::class           => InvokableFactory::class,
            ErrorPreviewConsoleController::class    => InvokableFactory::class,
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
                            'controller' => ErrorPreviewConsoleController::class,
                            'action'     => 'exception'
                        ],
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
            Mvc::class    => MvcFactory::class,
            Logging::class => LoggingFactory::class,
        ],
        'initializers' => [
            static function ($service, $instance) : void {
                if ($instance instanceof BaseLoggingCommand) {
                    $instance->init(
                        $service->get('config')['error-hero-module'],
                        $service->get('ErrorHeroModuleLogger')
                    );
                }
            }
        ],
    ],

    'listeners' => [
        Mvc::class,
    ],

    'view_manager' => [
        'template_map' => [
           'error-hero-module/error-default' => __DIR__.'/../view/error-hero-module/error-default.phtml',
       ],
    ],

];
