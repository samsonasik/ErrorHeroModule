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

        throw new RuntimeException(sprintf(
            'class %s not found',
            $class
        ));
    }
}