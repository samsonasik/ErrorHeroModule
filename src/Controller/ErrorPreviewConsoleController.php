<?php

namespace ErrorHeroModule\Controller;

use Zend\Mvc\Controller\AbstractConsoleController;

if (! \class_exists('Zend\Mvc\Controller\AbstractConsoleController')) {
    \class_alias(
        'Zend\Mvc\Console\Controller\AbstractConsoleController',
        'Zend\Mvc\Controller\AbstractConsoleController'
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
