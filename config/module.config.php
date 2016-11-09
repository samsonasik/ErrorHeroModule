<?php

namespace ErrorHeroModule;

return [
    'service_manager' => [
        'factories' => [
            Listner\Mvc::class => Listener\MvcFactory::class,
        ],
    ],
];
