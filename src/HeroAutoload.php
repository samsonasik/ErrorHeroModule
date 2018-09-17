<?php

declare(strict_types=1);

namespace ErrorHeroModule;

use RuntimeException;

class HeroAutoload
{
    public static function handle(string $class)
    {
        if (\class_exists($class, false)) {
            return;
        }

        $debugBacktrace = debug_backtrace();
        if ($debugBacktrace[1]['function'] === 'spl_autoload_call' &&
            $debugBacktrace[2]['function'] === 'class_exists'
        ) {
            return;
        }

        throw new RuntimeException(sprintf(
            'class %s not found',
            $class
        ));
    }
}