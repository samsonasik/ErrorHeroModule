<?php

namespace ErrorHeroModule\Controller;

use Zend\Mvc\Console\Controller\AbstractConsoleController;

if (! \class_exists(AbstractConsoleController::class)) {
    \class_alias(
        \Zend\Mvc\Controller\AbstractConsoleController::class,
        AbstractConsoleController::class
    );
}

class ErrorPreviewConsoleController extends AbstractConsoleController
{
    public function exceptionAction()
    {
        throw new \Exception('a sample error preview');
    }

    public function errorAction()
    {
        $array = [];
        $array[1]; // E_NOTICE
    }
}
