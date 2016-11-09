<?php

namespace ErrorHeroModule;

return [

    'service_manager' => [
        'factories' => [
            Listner\Mvc::class => Listener\MvcFactory::class,
        ],
    ],

    'view_manager' => [
        'template_map' => [
           'error-hero-module/error-default' => __DIR__.'/../view/error-hero-module/error-default.phtml',
       ],
    ],

];
