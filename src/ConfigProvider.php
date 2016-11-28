<?php

namespace ErrorHeroModule;

class ConfigProvider
{
    public function __invoke()
    {
        $config = include __DIR__.'/../config/module.config.php';

        return [
            'dependencies' => [
                'factories' => [
                    Middleware\ErrorAction::class => Middleware\ErrorActionFactory::class,
                    Middleware\ExceptionAction::class => Middleware\ExceptionActionFactory::class,
                ],
            ],
        ];
    }
}
