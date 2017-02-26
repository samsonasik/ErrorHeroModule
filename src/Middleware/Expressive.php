<?php

namespace ErrorHeroModule\Middleware;

class Expressive
{
    public function __invoke($request, $response, callable $next)
    {
        return $next($request, $response);
    }
}
