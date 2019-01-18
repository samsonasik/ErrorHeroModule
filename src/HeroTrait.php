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

    /** @var string */
    private $result = '';

    public function phpFatalErrorHandler($buffer): string
    {
        $error = \error_get_last();
        if (! $error) {
            return $buffer;
        }

        if (0 === strpos($error['message'], 'Uncaught')) {
            return $buffer;
        }

        return $this->result === '' ? $buffer : $this->result;
    }

    public function execOnShutdown() : void
    {
        $error = \error_get_last();
        if (! $error) {
            return;
        }

        if (0 === strpos($error['message'], 'Uncaught')) {
            return;
        }

        $errorException = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);

        // expressive project
        if ($this instanceof Middleware\Expressive) {
            $result       = $this->exceptionError($errorException, $this->request);
            $this->result = (string) $result->getBody();

            return;
        }

        // mvc project
        ob_start();
        $this->mvcEvent->setParam('exception', $errorException);
        $this->exceptionError($this->mvcEvent);
        $this->result = (string) ob_get_clean();
    }

    /**
     * @throws ErrorException when php error happen and error type is not excluded in the config
     */
    public function phpErrorHandler(int $errorType, string $errorMessage, string $errorFile, int $errorLine) : void
    {
        if (! (\error_reporting() & $errorType)) {
            return;
        }

        if (\in_array($errorType, $this->errorHeroModuleConfig['display-settings']['exclude-php-errors'])) {
            return;
        }

        throw new ErrorException($errorMessage, 0, $errorType, $errorFile, $errorLine);
    }
}
