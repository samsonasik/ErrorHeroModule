<?php

namespace ErrorHeroModule\Listener;

class MvcFactory
{
    public function __invoke($container)
    {
        $config = $container->get('config');
        $defaultHeroConfig = [
            'enable' => true,
            'display-settings' => [

                // excluded php errors
                'exclude-php-errors' => [
                    E_USER_DEPRECATED
                ],

                // show or not error
                'display_errors'  => 1,

                // if enable and display_errors = 0
                'view_errors' => 'error-hero-module/error-default'
            ],
            'logging-settings' => [
                'same-error' => 86400,
            ],
            'email-notification-settings' => [
                // set to true to activate email notification on log error
                'enable' => false,

                'mail-service'   => 'YourMailService',   // Zend\Mail\Message instance registered at service manager
                'mail-transport' => 'YourMailTransport', // Zend\Mail\Transport\TransportInterface instance registered at service manager

                'email-to-send' => [
                    'developer1@foo.com',
                    'developer2@foo.com',
                ],
            ],
        ];

        $errorHeroModuleConfig = isset($config['error-hero-module'])
            ? $config['error-hero-module']
            : $defaultHeroConfig;

        return new Mvc($errorHeroModuleConfig);
    }
}
