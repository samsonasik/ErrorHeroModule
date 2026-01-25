<?php

declare(strict_types=1);

namespace ErrorHeroModule;

use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use const E_CORE_ERROR;
use const E_CORE_WARNING;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_RECOVERABLE_ERROR;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;

final class HeroConstant
{
    /** @var array<int, string> */
    public const array ERROR_TYPE = [
        E_ERROR           => 'E_ERROR',
        E_WARNING         => 'E_WARNING',
        E_PARSE           => 'E_PARSE',
        E_NOTICE          => 'E_NOTICE',
        E_CORE_ERROR      => 'E_CORE_ERROR',
        E_CORE_WARNING    => 'E_CORE_WARNING',
        E_COMPILE_ERROR   => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR      => 'E_USER_ERROR',
        E_USER_WARNING    => 'E_USER_WARNING',
        E_USER_NOTICE     => 'E_USER_NOTICE',
        // E_STRICT is deprecated in php 8.4
        2048                => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED        => 'E_DEPRECATED',
        E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
    ];
}
