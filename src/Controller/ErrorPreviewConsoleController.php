<?php

declare(strict_types=1);

namespace ErrorHeroModule\Controller;

use Error;
use Exception;
use Laminas\Mvc\Console\Controller\AbstractConsoleController;

class ErrorPreviewConsoleController extends AbstractConsoleController
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
}
