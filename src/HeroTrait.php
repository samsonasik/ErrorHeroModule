<?php

namespace ErrorHeroModule;

use ErrorHeroModule\Handler\Logging;
use Zend\Diactoros\ServerRequest;
use Zend\View\Renderer\PhpRenderer;

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

    /**
     * @var PhpRenderer
     */
    private $renderer;

    private $errorType = [
        E_ERROR             => 'E_ERROR',
        E_WARNING           => 'E_WARNING',
        E_PARSE             => 'E_PARSE',
        E_NOTICE            => 'E_NOTICE',
        E_CORE_ERROR        => 'E_CORE_ERROR',
        E_CORE_WARNING      => 'E_CORE_WARNING',
        E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
        E_USER_ERROR        => 'E_USER_ERROR',
        E_USER_WARNING      => 'E_USER_WARNING',
        E_USER_NOTICE       => 'E_USER_NOTICE',
        E_STRICT            => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED        => 'E_DEPRECATED',
        E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
    ];

    /**
     * @var ServerRequest|null
     */
    private $request = null;

    /**
     * @return void
     */
    public function execOnShutdown()
    {
        $error = error_get_last();
        if ($error && $error['type']) {
            $this->phpErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    /**
     * @param int    $errorType
     * @param string $errorMessage
     * @param string $errorFile
     * @param int    $errorLine
     *
     * @return void
     */
    public function phpErrorHandler($errorType, $errorMessage, $errorFile, $errorLine)
    {
        $errorTypeString = $this->errorType[$errorType];
        $errorExcluded = false;
        if ($errorLine) {
            if (in_array($errorType, $this->errorHeroModuleConfig['display-settings']['exclude-php-errors'])) {
                $errorExcluded = true;
            } else {
                $this->logging->handleError(
                    $errorType,
                    $errorMessage,
                    $errorFile,
                    $errorLine,
                    $errorTypeString
                );
            }
        }

        if ($this->errorHeroModuleConfig['display-settings']['display_errors'] === 0 || $errorExcluded) {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', 0);
        }

        if (! $errorExcluded) {
            $this->showDefaultViewWhenDisplayErrorSetttingIsDisabled($this->request);
        }
    }
}
