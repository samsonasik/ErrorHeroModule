<?php

declare(strict_types=1);

namespace ErrorHeroModule\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class ErrorPreviewController extends AbstractActionController
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
