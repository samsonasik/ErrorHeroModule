<?php

namespace ErrorHeroModule\Middleware\Routed\Preview;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ErrorPreviewAction
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $action = $request->getAttribute('action', 'exception');

        if ($action === 'exception') {
            throw new \Exception('a sample error preview');
        }

        if ($action === 'php7error' && class_exists(\Error::class)) {
            throw new \Error('error of php 7');
        }

        $array = [];
        $array[1]; // E_NOTICE

        return $response;
    }
}
