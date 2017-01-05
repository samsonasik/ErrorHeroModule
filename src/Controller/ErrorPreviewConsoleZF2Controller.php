<?php

namespace ErrorHeroModule\Controller;

use Zend\Mvc\Controller\AbstractConsoleController;
use Zend\View\Model\ConsoleModel;

class ErrorPreviewConsoleZF2Controller extends AbstractConsoleController
{
    public function exceptionAction()
    {
        throw new \Exception('a sample error preview');
    }

    public function errorAction()
    {
        $array = [];
        $array[1]; // E_NOTICE

        return new ConsoleModel();
    }
}
