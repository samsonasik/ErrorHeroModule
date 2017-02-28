<?php

namespace ErrorHeroModule\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Expressive
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        try {
            return $next($request, $response);
        } catch (\Throwable $t) {
        } catch (\Exception $e) {
        }
    }
}
