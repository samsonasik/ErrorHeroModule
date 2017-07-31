<?php

namespace ErrorHeroModule\Controller;

use Zend\Mvc\Console\Controller\AbstractConsoleController;

if (\class_exists(AbstractConsoleController::class)) {
    \class_alias(
        AbstractConsoleController::class,
        \Zend\Mvc\Controller\AbstractConsoleController::class
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
