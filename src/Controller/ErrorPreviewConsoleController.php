<?php

namespace ErrorHeroModule\Controller;

use Zend\Mvc\Console\Controller\AbstractConsoleController;

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
