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

        $array = [];
        $array[1]; // E_NOTICE
    }
}
