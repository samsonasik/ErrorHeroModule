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

    /**
     * @param array $errorHeroModuleConfig
     */
    public function __construct(array $errorHeroModuleConfig)
    {
        $this->errorHeroModuleConfig = $errorHeroModuleConfig;
    }

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        // exceptions
        $this->listeners[] = $events->attach(MvcEvent::EVENT_RENDER_ERROR, [$this, 'renderError']);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'dispatchError'], 100);

        // php errors
        $this->listeners[] = $events->attach('*', [$this, 'phpError']);
    }

    public function renderError($e)
    {

    }

    public function dispatchError($e)
    {

    }

    public function phpError($e)
    {
        if ($this->errorHeroModuleConfig['options']['display_errors'] === 0) {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors',0);
        }


    }
}
