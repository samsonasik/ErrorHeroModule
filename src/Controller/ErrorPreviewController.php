<?php

declare(strict_types=1);

namespace ErrorHeroModule\Controller;

use Error;
use Exception;
use Laminas\Mvc\Controller\AbstractActionController;
use stdClass;

class ErrorPreviewController extends AbstractActionController
{
    public function exceptionAction()
    {
        throw new Exception('a sample exception preview');
    }

    public function errorAction()
    {
        throw new Error('a sample error preview');
    }

    public function noticeAction()
    {
        $array = [];
        $array[1]; // E_NOTICE
    }

    public function fatalAction()
    {
        $y = new class implements stdClass {
        };
    }
}
