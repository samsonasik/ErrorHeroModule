<?php

namespace ErrorHeroModule\Listener;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\Mvc\MvcEvent;

class Mvc extends AbstractListenerAggregate
{
    public function attach(EventManagerInterface $events)
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

    }
}
