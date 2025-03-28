<?php

namespace ErrorHeroModule;

use ErrorHeroModule\Command\BaseLoggingCommandInitializer;
use ErrorHeroModule\Command\Preview\ErrorPreviewConsoleCommand;
use ErrorHeroModule\Controller\ErrorPreviewController;
use ErrorHeroModule\Listener\Mvc;
use ErrorHeroModule\Listener\MvcFactory;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Handler\LoggingFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [

    'controllers' => [
        'factories' => [
            ErrorPreviewController::class           => InvokableFactory::class,
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

    'service_manager' => [
        'abstract_factories' => [
        ],
        'factories' => [
            Mvc::class    => MvcFactory::class,
            Logging::class => LoggingFactory::class,
            ErrorPreviewConsoleCommand::class => InvokableFactory::class,
            'ErrorHeroModuleLogger' => fn (): LoggerInterface => new \Monolog\Logger('error-hero-module'),
        ],
        'initializers' => [
            BaseLoggingCommandInitializer::class,
        ],
    ],

    'listeners' => [
        Mvc::class,
    ],

    'laminas-cli' => [
        'commands' => [
            'errorheromodule:preview' => ErrorPreviewConsoleCommand::class,
        ],
    ],

    'view_manager' => [
        'template_map' => [
           'error-hero-module/error-default' => __DIR__.'/../view/error-hero-module/error-default.phtml',
       ],
    ],

];
