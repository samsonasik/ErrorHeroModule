<?php

namespace ErrorHeroModule;

class ConfigProvider
{
    public function __invoke()
    {
        $config = include __DIR__.'/../config/module.config.php';
        return [
            'dependencies' => $config['service_manager'],
        ];
    }
}
