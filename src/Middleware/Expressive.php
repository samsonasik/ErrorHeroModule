<?php

namespace ErrorHeroModule\Middleware;

class Expressive
{
    public function __invoke($request, $response, callable $next)
    {
        echo 'test';die;
    }
}
