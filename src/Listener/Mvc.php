<?php

namespace ErrorHeroModule\Listener;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\Mvc\MvcEvent;

class Mvc extends AbstractListenerAggregate
{
    /**
     * @var array
     */
    private $errorHeroModuleConfig;

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
    public function __construct(array $errorHeroModuleConfig)
    {
        $this->errorHeroModuleConfig = $errorHeroModuleConfig;
    }

    /**
     * @param  EventManagerInterface $events
     * @param  int                   $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        // exceptions
        $this->listeners[] = $events->attach(MvcEvent::EVENT_RENDER_ERROR, [$this, 'renderError']);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'dispatchError'], 100);

        // php errors
        $this->listeners[] = $events->attach('*', [$this, 'phpError']);
    }

    private function handleException($e)
    {
        $exception = $e->getParam('exception');
        if (! $exception) {
            return;
        }
    }

    public function renderError($e)
    {
        $this->handleException($e);
    }

    public function dispatchError($e)
    {
        $this->handleException($e);
    }

    public function phpError($e)
    {
        if ($this->errorHeroModuleConfig['options']['display_errors'] === 0) {
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
        if(! $errorLine   ||
            in_array($this->errorHeroModuleConfig['options']['exclude-php-errors'], array_keys($this->errorType))
        )  {
            return;
        }


    }
}
