<?php

namespace ErrorHeroModule\Listener;

class MvcFactory
{
    public function __invoke($container)
    {
        $config = $container->get('config');
        $errorHeroModuleConfig = isset($config['error-hero-module'])
            ? $config['error-hero-module']
            : [
                'enable' => true,
                'options' => [
                    'exclude-php-errors' => [],
                    'display_errors'  => 0,
                    'view_errors' => 'error-hero-module/error-default'
                ],
                'logging' => [
                    'range-same-error' => 86400,
                    'adapters' => [
                        'stream' => [
                            'path' => '/var/log'
                        ],
                        'db' => [
                            'zend-db-adapter' => 'Zend\Db\Adapter\Adapter',
                            'table'           => 'log'
                        ],
                    ],
                ],
                'email-notification' => [],
            ];

        return new Mvc($errorHeroModuleConfig);
    }
}
