<?php

namespace ErrorHeroModule;

use Zend\Log;

return [

    'service_manager' => [
        'abstract_factories' => [
            Log\LoggerAbstractServiceFactory::class,
        ],
        'factories' => [
            Listener\Mvc::class => Listener\MvcFactory::class,
            Handler\Logging::class => Handler\LoggingFactory::class,
        ],
    ],

    'view_manager' => [
        'template_map' => [
           'error-hero-module/error-default' => __DIR__.'/../view/error-hero-module/error-default.phtml',
       ],
    ],

];
