<?php

namespace ErrorHeroModule\Controller;

if (\class_exists(\Zend\Mvc\Console\Controller\AbstractConsoleController::class)) {
    \class_alias(
        \Zend\Mvc\Console\Controller\AbstractConsoleController::class,
        \Zend\Mvc\Controller\AbstractConsoleController::class
    );
}

use Zend\Mvc\Controller\AbstractConsoleController;

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
