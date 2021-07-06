<?php

declare(strict_types=1);

namespace ErrorHeroModule\Middleware\Routed\Preview;

use Error;
use Exception;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;

class ErrorPreviewAction implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $serverRequest): ResponseInterface
    {
        $action = $serverRequest->getAttribute('action', 'exception');

        if ($action === 'exception') {
            throw new Exception('a sample exception preview');
        }

        if ($action === 'error') {
            throw new Error('a sample error preview');
        }

        if ($action === 'fatal') {
            $y = new class implements stdClass {
            };
        }

        $array = [];
        $array[1]; // E_WARNING

        return new Response();
    }
}
