<?php

namespace ErrorHeroModule\Listener;

use ErrorHeroModule\Handler\Logging;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\Mvc\MvcEvent;

class Mvc extends AbstractListenerAggregate
{
    /**
     * @var array
     */
    private $errorHeroModuleConfig;

    /**
     * @var Logging
     */
    private $logging;

    private $errorType = [
        E_ERROR              => 'E_ERROR',
        E_WARNING            => 'E_WARNING',
        E_PARSE              => 'E_PARSE',
        E_NOTICE             => 'E_NOTICE',
        E_CORE_ERROR         => 'E_CORE_ERROR',
        E_CORE_WARNING       => 'E_CORE_WARNING',
        E_COMPILE_ERROR      => 'E_COMPILE_ERROR',
        E_CORE_WARNING       => 'E_CORE_WARNING',
        E_USER_ERROR         => 'E_USER_ERROR',
        E_USER_WARNING       => 'E_USER_WARNING',
        E_USER_NOTICE        => 'E_USER_NOTICE',
        E_STRICT             => 'E_STRICT',
        E_RECOVERABLE_ERROR  => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED         => 'E_DEPRECATED',
        E_USER_DEPRECATED    => 'E_USER_DEPRECATED',
    ];

    /**
     * @param array $errorHeroModuleConfig
     */
    public function __construct(
        array $errorHeroModuleConfig,
        Logging $logging
    ) {
        $this->errorHeroModuleConfig = $errorHeroModuleConfig;
        $this->logging               = $logging;
    }

    /**
     * @param  EventManagerInterface $events
     * @param  int                   $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        if ($this->errorHeroModuleConfig['enable'] === true) {
            // exceptions
            $this->listeners[] = $events->attach(MvcEvent::EVENT_RENDER_ERROR, [$this, 'exceptionError']);
            $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'exceptionError'], 100);

            // php errors
            $this->listeners[] = $events->attach('*', [$this, 'phpError']);
        }
    }

    public function exceptionError($e)
    {
        $exception = $e->getParam('exception');
        if (! $exception) {
            return;
        }

        $this->logging->handleException(
            $exception
        );
    }

    public function phpError($e)
    {
        if ($this->errorHeroModuleConfig['display-settings']['display_errors'] === 0) {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors',0);
        }

        register_shutdown_function([$this, 'execOnShutdown']);
        set_error_handler([$this, 'phpErrorHandler']);
    }

    public function execOnShutdown()
    {
        $error = error_get_last();
        if ($error && $error['type']) {
            $this->phpErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    /**
     * @param  int    $errorType
     * @param  string $errorMessage
     * @param  string $errorFile
     * @param  int    $errorLine
     */
    public function phpErrorHandler($errorType, $errorMessage, $errorFile, $errorLine)
    {
        $errorTypeString = $this->errorType[$errorType];
        if (! $errorLine   ||
            in_array(
                $this->errorHeroModuleConfig['display-settings']['exclude-php-errors'],
                array_keys($this->errorType)
            )
        )  {
            return;
        }

        $this->logging->handleError(
            $errorType,
            $errorMessage,
            $errorFile,
            $errorLine,
            $errorTypeString
        );
    }
}
