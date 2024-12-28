<?php

declare(strict_types=1);

namespace ErrorHeroModule\Compat;

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

final class Logger extends \Laminas\Log\Logger
{
    /** @var array<int, int> */
    public static $errorPriorityMap = [
        E_NOTICE            => parent::NOTICE,
        E_USER_NOTICE       => parent::NOTICE,
        E_WARNING           => parent::WARN,
        E_CORE_WARNING      => parent::WARN,
        E_USER_WARNING      => parent::WARN,
        E_ERROR             => parent::ERR,
        E_USER_ERROR        => parent::ERR,
        E_CORE_ERROR        => parent::ERR,
        E_RECOVERABLE_ERROR => parent::ERR,
        E_PARSE             => parent::ERR,
        E_COMPILE_ERROR     => parent::ERR,
        E_COMPILE_WARNING   => parent::ERR,
        // E_STRICT is deprecated in php 8.4
        2048              => parent::DEBUG,
        E_DEPRECATED      => parent::DEBUG,
        E_USER_DEPRECATED => parent::DEBUG,
    ];
}
