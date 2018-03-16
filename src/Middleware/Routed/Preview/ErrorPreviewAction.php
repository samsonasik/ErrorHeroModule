<?php

declare(strict_types=1);

namespace ErrorHeroModule\Middleware\Routed\Preview;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ErrorPreviewAction implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $action = $request->getAttribute('action', 'exception');

        if ($action === 'exception') {
            throw new \Exception('a sample exception preview');
        }

        if ($action === 'error') {
            throw new \Error('a sample error preview');
        }

        $array = [];
        $array[1]; // E_NOTICE
    }
}
