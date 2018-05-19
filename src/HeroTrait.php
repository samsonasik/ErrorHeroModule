<?php

declare(strict_types=1);

namespace ErrorHeroModule;

use ErrorException;
use ErrorHeroModule\Handler\Logging;

trait HeroTrait
{
    /**
     * @var array
     */
    private $errorHeroModuleConfig;

    /**
     * @var Logging
     */
    private $logging;

    public function execOnShutdown() : void
    {
        $error = \error_get_last();
        if (! $error) {
            return;
        }

        $this->phpErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
    }

    /**
     * @throws ErrorException when php error happen and error type is not excluded in the config
     */
    public function phpErrorHandler(int $errorType, string $errorMessage, string $errorFile, int $errorLine) : void
    {
        if (! (\error_reporting() & $errorType)) {
            return;
        }

        if (! $this->errorHeroModuleConfig['display-settings']['display_errors']) {
            \error_reporting(\E_ALL | \E_STRICT);
            \ini_set('display_errors', '0');
        }

        if (\in_array($errorType, $this->errorHeroModuleConfig['display-settings']['exclude-php-errors'])) {
            return;
        }

        throw new ErrorException($errorMessage, 500, $errorType, $errorFile, $errorLine);
    }
}
