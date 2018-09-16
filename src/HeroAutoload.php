<?php

declare(strict_types=1);

namespace ErrorHeroModule;

use RuntimeException;

class HeroAutoload
{
    public static function handle(string $class)
    {
        if (\class_exists($class)) {
            return;
        }

        if (\in_array(
            $class,
            [
                'error_reporting',
                'error_get_last',
                'ErrorHeroModuleLogger',
                'ZendDeveloperTools\\ProfilerEvent',
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