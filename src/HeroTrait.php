<?php

declare(strict_types=1);

namespace ErrorHeroModule;

use ErrorException;
use ErrorHeroModule\Handler\Logging;
use ErrorHeroModule\Middleware\Expressive;

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

    /** @var string */
    private $result;

    public function phpFatalErrorHandler($buffer): string
    {
        $error = \error_get_last();
        if (! $error) {
            return $buffer;
        }

        http_response_code(500);
        return $this->result;
    }

    public function execOnShutdown() : void
    {
        $error = \error_get_last();
        if (! $error) {
            return;
        }

        $t                 = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
        $displayFatalError = 'Fatal error: ' . $t->getMessage() . ' in ' . $error['file'] . ' on line ' . $error['line'];

        try {
            if (static::class === Expressive::class) {
                $result = $this->exceptionError($t, $this->request);
                /** @var \Psr\Http\Message\ResponseInterface $result */
                $this->result = (string) $result->getBody();

                return;
            }

            if ($this->errorHeroModuleConfig['display-settings']['display_errors']) {
                $this->result = $displayFatalError;
                return;
            }

            ob_start();
            $this->mvcEvent->setParam('exception', $t);
            $this->exceptionError($this->mvcEvent);
            $this->result = ob_get_clean();
        } catch (ErrorException $t) {
            $this->result = $displayFatalError;
        }
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

        throw new ErrorException($errorMessage, 0, $errorType, $errorFile, $errorLine);
    }
}
