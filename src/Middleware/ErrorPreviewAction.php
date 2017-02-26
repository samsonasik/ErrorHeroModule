<?php

namespace ErrorHeroModule\Middleware;

use Zend\Diactoros\Response;

class ErrorPreviewAction
{
    public function __invoke($request, $response, callable $next)
    {
        $action = $request->getParam('action', 'exception');

        if ($action === 'exception') {
            throw new \Exception('test');
        }

        $array = [];
        $array[1]; // E_NOTICE

        return new Response();
    }
}
