<?php

namespace ErrorHeroModule;

use RuntimeException;

class HeroAutoload
{
    public static function handle($class)
    {
        if (\class_exists($class, false)) {
            return;
        }

        if (\in_array(
            $class,
            [
                'error_reporting',
                'error_get_last',
                'ErrorHeroModuleLogger',
                'ZendDeveloperTools\\ProfilerEvent',
                'Zend\Version\Version',
                'ZF\MvcAuth\MvcAuthEvent',
                'Zend\EventManager\GlobalEventManager',
                'Zend\Session\ValidatorChain',
            ]
        )) {
            return;
        }

        throw new RuntimeException(sprintf(
            'class %s not found',
            $class
        ));
    }
}